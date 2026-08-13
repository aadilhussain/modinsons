<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\Images\ImageFetchException;
use App\Support\Images\ProductImageFetcher;
use DOMDocument;
use DOMElement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Bulk catalogue photography, in four steps that can be run independently:
 *
 *   1. --worksheet=file.csv          list the products still missing a photo
 *   2. --scan=URL --worksheet=f.csv  propose a source URL per product from a
 *                                    supplier listing page (writes no images)
 *   3. --csv=file.csv                queue the reviewed URLs against products
 *   4. (no options)                  download everything queued and commit it
 *
 * Or skip the web entirely with --dir=folder, which matches files named after
 * the product code (1001.jpg, MS-PVC-001.png) — the route to take when a
 * supplier hands over a brochure PDF or a zip of shots.
 *
 * Steps 2 and 3 are deliberately separate. An automatic pairing of photo to
 * model code is a guess; a wrong guess published on a B2B catalogue is a
 * wrong-goods dispute, so a human reads the CSV in between.
 */
class CatalogueImagesCommand extends Command
{
    protected $signature = 'catalogue:images
        {--worksheet= : Write a CSV of products and their image URLs to this path}
        {--scan= : Read a supplier listing page and propose an image URL per product}
        {--csv= : Queue image URLs from a CSV with sku and image_url columns}
        {--dir= : Attach image files from a folder, matched on product code}
        {--category= : Limit to one category slug}
        {--limit=0 : Stop after this many products}
        {--size=900 : Output width and height in pixels}
        {--delay=1 : Seconds to wait between downloads}
        {--force : Replace photos that are already set}
        {--dry-run : Report what would happen and write nothing}';

    protected $description = 'Fetch, normalise and commit product photos for the catalogue';

    public function handle(): int
    {
        try {
            return match (true) {
                (bool) $this->option('scan') => $this->scan(),
                (bool) $this->option('csv') => $this->queueFromCsv(),
                (bool) $this->option('dir') => $this->attachFromDir(),
                (bool) $this->option('worksheet') => $this->worksheet($this->products()->get()),
                default => $this->download(),
            };
        } catch (ImageFetchException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /** Products in scope: missing a photo unless --force, optionally one category. */
    protected function products()
    {
        return Product::with('category')
            ->when($this->option('category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when(! $this->option('force'), fn ($q) => $q->where(fn ($w) => $w->whereNull('image_path')->orWhere('image_path', '')))
            ->when((int) $this->option('limit') > 0, fn ($q) => $q->limit((int) $this->option('limit')))
            ->orderBy('category_id')->orderBy('sku')->orderBy('name');
    }

    // ---------------------------------------------------------------- step 1

    protected function worksheet($products): int
    {
        $path = $this->option('worksheet');
        $handle = fopen($path, 'w');

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['sku', 'name', 'brand', 'category', 'image_url', 'note']);

        foreach ($products as $product) {
            fputcsv($handle, [
                $product->sku,
                $product->name,
                $product->brand,
                $product->category?->name,
                $product->image_source,
                '',
            ]);
        }

        fclose($handle);

        $this->components->info(count($products).' products written to '.$path);
        $this->line('  Fill the <options=bold>image_url</> column, then run: <options=bold>php artisan catalogue:images --csv='.$path.'</>');

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- step 2

    /**
     * Read one supplier listing page and guess which image belongs to which
     * product, by looking for the model code in the image filename, its alt
     * text, or the product name in either.
     */
    protected function scan(): int
    {
        $url = $this->option('scan');

        if (! $this->option('worksheet')) {
            $this->components->error('--scan needs --worksheet=path.csv to write its proposals to.');

            return self::FAILURE;
        }

        $this->components->info('Reading '.$url);

        $response = Http::withHeaders(['User-Agent' => 'ModiAndSonsCatalogue/1.0 (+https://modiandsons.com; catalogue image sync)'])
            ->timeout(30)->get($url);

        if (! $response->successful()) {
            $this->components->error('HTTP '.$response->status().' from '.$url);

            return self::FAILURE;
        }

        $candidates = $this->imagesOn($response->body(), $url);
        $this->line('  '.count($candidates).' images found on the page.');

        $products = $this->products()->get();
        $matched = 0;

        foreach ($products as $product) {
            if ($guess = $this->bestMatch($product, $candidates)) {
                $product->image_source = $guess;
                $matched++;
            }
        }

        $this->components->warn('Proposed '.$matched.' of '.count($products).' — open the CSV and check every row before importing it.');

        // The guesses live only in memory here; the worksheet is the record.
        return $this->worksheet($products);
    }

    /**
     * @return array<int, array{url: string, hay: string}>
     */
    protected function imagesOn(string $html, string $base): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $out = [];

        foreach ($dom->getElementsByTagName('img') as $img) {
            /** @var DOMElement $img */
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: $img->getAttribute('data-original');

            // Lazy-loading galleries often keep the real file in srcset only.
            if ($src === '' && $img->getAttribute('srcset') !== '') {
                $src = trim(explode(' ', trim(explode(',', $img->getAttribute('srcset'))[0]))[0]);
            }

            if ($src === '' || str_starts_with($src, 'data:')) {
                continue;
            }

            $url = $this->absolute($src, $base);
            $out[] = [
                'url' => $url,
                'hay' => Str::lower(basename(parse_url($url, PHP_URL_PATH) ?? '').' '.$img->getAttribute('alt').' '.$img->getAttribute('title')),
            ];
        }

        return $out;
    }

    /** @param array<int, array{url: string, hay: string}> $candidates */
    protected function bestMatch(Product $product, array $candidates): ?string
    {
        $sku = Str::lower(trim((string) $product->sku));
        $name = Str::slug($product->name);

        foreach ($candidates as $c) {
            $hay = $c['hay'];

            // A bare model code has to match on a word boundary, or "1001"
            // happily matches "11001" and "2010012".
            if ($sku !== '' && preg_match('/(?<![0-9a-z])'.preg_quote($sku, '/').'(?![0-9a-z])/i', $hay)) {
                return $c['url'];
            }
        }

        foreach ($candidates as $c) {
            if ($name !== '' && str_contains(Str::slug($c['hay']), $name)) {
                return $c['url'];
            }
        }

        return null;
    }

    protected function absolute(string $src, string $base): string
    {
        if (preg_match('~^https?://~i', $src)) {
            return $src;
        }

        $parts = parse_url($base);
        $root = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($src, '//')) {
            return $parts['scheme'].':'.$src;
        }

        $path = str_starts_with($src, '/')
            ? $src
            : rtrim(dirname($parts['path'] ?? '/'), '/').'/'.$src;

        // Resolve ../ and ./ properly. Trimming the dots off instead — the
        // obvious shortcut — turns ../../uploads/1001.jpg into a path two
        // directories deeper than the supplier meant, and every fetch 404s.
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            match ($segment) {
                '', '.' => null,
                '..' => array_pop($segments),
                default => $segments[] = $segment,
            };
        }

        return $root.'/'.implode('/', $segments);
    }

    // ---------------------------------------------------------------- step 3

    protected function queueFromCsv(): int
    {
        $path = $this->option('csv');

        if (! is_readable($path)) {
            $this->components->error('Cannot read '.$path);

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = array_map(
            fn ($h) => Str::snake(Str::lower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF"))),
            (array) fgetcsv($handle)
        );

        $queued = 0;
        $missed = [];

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
            $url = trim((string) ($row['image_url'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($url === '') {
                continue;
            }

            $product = Product::query()
                ->when($sku !== '', fn ($q) => $q->where('sku', $sku), fn ($q) => $q->where('name', $name))
                ->first();

            if (! $product) {
                $missed[] = $sku ?: $name;

                continue;
            }

            if (! $this->option('dry-run')) {
                $product->forceFill(['image_source' => $url])->save();
            }

            $queued++;
        }

        fclose($handle);

        $this->components->info($queued.' image URLs queued'.($this->option('dry-run') ? ' (dry run — nothing saved)' : '.'));

        if ($missed !== []) {
            $this->components->warn(count($missed).' rows matched no product: '.implode(', ', array_slice($missed, 0, 10)).(count($missed) > 10 ? '…' : ''));
        }

        return $this->option('dry-run') ? self::SUCCESS : $this->download();
    }

    // ---------------------------------------------------------------- step 4

    protected function download(): int
    {
        $products = $this->products()->whereNotNull('image_source')->where('image_source', '!=', '')->get();

        if ($products->isEmpty()) {
            $this->components->warn('Nothing queued. Give products an image_source first — see --csv, --scan or --dir.');

            return self::SUCCESS;
        }

        $fetcher = new ProductImageFetcher((int) $this->option('size'));
        $delay = (float) $this->option('delay');
        $done = $failed = 0;

        $this->components->info('Fetching '.$products->count().' images'.($this->option('dry-run') ? ' (dry run)' : ''));
        $bar = $this->output->createProgressBar($products->count());

        foreach ($products as $product) {
            try {
                if ($this->option('dry-run')) {
                    $this->line("\n  would fetch {$product->sku} ← {$product->image_source}");
                } else {
                    $path = $fetcher->fromUrl($product, $product->image_source);
                    $product->forceFill(['image_path' => $path])->save();
                }

                $done++;
            } catch (ImageFetchException $e) {
                $failed++;
                $this->newLine();
                $this->components->warn(($product->sku ?: $product->name).': '.$e->getMessage());
            }

            $bar->advance();

            // Courtesy to the source: a catalogue sync is not a load test.
            if ($delay > 0 && ! $this->option('dry-run')) {
                usleep((int) ($delay * 1_000_000));
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->components->info($done.' images committed to public/assets/products'.($failed ? ", {$failed} failed" : '.'));
        $this->bust();

        return self::SUCCESS;
    }

    // ------------------------------------------------------------ folder drop

    protected function attachFromDir(): int
    {
        $dir = rtrim($this->option('dir'), '/');
        $files = glob($dir.'/*.{jpg,jpeg,png,webp,gif,bmp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];

        if ($files === []) {
            $this->components->error('No image files in '.$dir);

            return self::FAILURE;
        }

        // Index on the filename stem so both "1001.jpg" and "MS-PVC-001.png"
        // find their product, whatever case or padding the supplier used.
        $byStem = [];

        foreach ($files as $file) {
            $byStem[Str::lower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }

        $fetcher = new ProductImageFetcher((int) $this->option('size'));
        $done = $failed = $unmatched = 0;

        foreach ($this->products()->get() as $product) {
            $keys = array_filter([Str::lower((string) $product->sku), Str::lower($product->slug), Str::slug($product->name)]);
            $file = null;

            foreach ($keys as $key) {
                if (isset($byStem[$key])) {
                    $file = $byStem[$key];
                    break;
                }
            }

            if (! $file) {
                $unmatched++;

                continue;
            }

            try {
                if ($this->option('dry-run')) {
                    $this->line('  would attach '.basename($file).' → '.($product->sku ?: $product->name));
                } else {
                    $path = $fetcher->fromFile($product, $file);
                    $product->forceFill(['image_path' => $path, 'image_source' => 'file:'.basename($file)])->save();
                }

                $done++;
            } catch (ImageFetchException $e) {
                $failed++;
                $this->components->warn(basename($file).': '.$e->getMessage());
            }
        }

        $this->components->info($done.' attached, '.$unmatched.' products still without a file'.($failed ? ", {$failed} failed" : '.'));
        $this->bust();

        return self::SUCCESS;
    }

    /** The homepage and nav cache product rows, photos included. */
    protected function bust(): void
    {
        if ($this->option('dry-run')) {
            return;
        }

        foreach (['nav.categories', 'home.featured', 'home.categories', 'home.stats', 'filter.brands'] as $key) {
            Cache::forget($key);
        }
    }
}
