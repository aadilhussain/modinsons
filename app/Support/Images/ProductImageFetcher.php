<?php

namespace App\Support\Images;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Turns a source image — a URL from a supplier catalogue or a file on disk —
 * into a committed catalogue photo.
 *
 * Images land in public/assets/products rather than on the public storage disk
 * because the site runs on Vercel, where storage/ lives in /tmp and is wiped
 * between invocations: anything uploaded there is gone by the next deploy.
 * /assets is already served statically (see vercel.json), so a committed file
 * is the only kind that survives.
 */
class ProductImageFetcher
{
    /** Anything smaller than this in either dimension is a logo or a spacer, not a product shot. */
    protected const MIN_SOURCE_PX = 200;

    protected const MAX_BYTES = 8 * 1024 * 1024;

    public function __construct(
        protected int $size = 900,
        protected int $quality = 82,
    ) {
        if (! function_exists('imagewebp')) {
            throw new ImageFetchException('PHP is built without GD/WebP support — install php-gd before running this.');
        }
    }

    /** Fetch a remote image and store it against the product. Returns the stored relative path. */
    public function fromUrl(Product $product, string $url): string
    {
        if (! preg_match('~^https?://~i', $url)) {
            throw new ImageFetchException('Not an http(s) URL.');
        }

        $response = Http::withHeaders([
            // Identify the crawler honestly: a supplier who wants to block or
            // rate-limit this can see exactly who it is.
            'User-Agent' => 'ModiAndSonsCatalogue/1.0 (+https://modiandsons.com; catalogue image sync)',
            'Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*',
        ])->timeout(25)->retry(2, 1500, throw: false)->get($url);

        if (! $response->successful()) {
            throw new ImageFetchException('HTTP '.$response->status().' from source.');
        }

        $type = strtolower((string) $response->header('Content-Type'));

        if ($type !== '' && ! str_starts_with($type, 'image/')) {
            throw new ImageFetchException('Source returned '.($type ?: 'no content type').', not an image.');
        }

        $binary = $response->body();

        if (strlen($binary) > self::MAX_BYTES) {
            throw new ImageFetchException('Source image is larger than 8 MB.');
        }

        return $this->store($product, $binary);
    }

    /** Store an image already on disk (a folder drop, or a page extracted from a PDF). */
    public function fromFile(Product $product, string $path): string
    {
        if (! is_readable($path)) {
            throw new ImageFetchException('Cannot read '.$path);
        }

        return $this->store($product, (string) file_get_contents($path));
    }

    /**
     * Normalise to a square white-backed WebP.
     *
     * Sanitaryware and electrical shots arrive at wildly different aspect
     * ratios; the catalogue grid assumes squares, so a mixed set makes the
     * cards jump. Padding rather than cropping keeps taps and cisterns whole.
     */
    protected function store(Product $product, string $binary): string
    {
        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            throw new ImageFetchException('File is not a readable image.');
        }

        $w = imagesx($source);
        $h = imagesy($source);

        if ($w < self::MIN_SOURCE_PX || $h < self::MIN_SOURCE_PX) {
            imagedestroy($source);

            throw new ImageFetchException("Source is only {$w}×{$h}px — too small to be a product photo.");
        }

        $canvas = imagecreatetruecolor($this->size, $this->size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        $scale = min($this->size / $w, $this->size / $h);
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        imagecopyresampled(
            $canvas, $source,
            (int) (($this->size - $tw) / 2), (int) (($this->size - $th) / 2), 0, 0,
            $tw, $th, $w, $h
        );

        $relative = 'assets/products/'.$this->filename($product);
        $absolute = public_path($relative);

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        $ok = imagewebp($canvas, $absolute, $this->quality);

        imagedestroy($canvas);
        imagedestroy($source);

        if (! $ok) {
            throw new ImageFetchException('Could not write '.$relative);
        }

        return $relative;
    }

    /** Readable, stable and collision-proof: the product id is the tiebreaker. */
    protected function filename(Product $product): string
    {
        $base = Str::slug(trim(($product->sku ? $product->sku.' ' : '').$product->name)) ?: 'product';

        return Str::limit($base, 50, '').'-'.$product->id.'.webp';
    }
}
