<?php

namespace App\Support\Import;

use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Turns an uploaded catalogue file into raw rows of [column => value].
 *
 * Only reads and normalises — deciding what the rows mean is CatalogueMapper's
 * job, and nothing here touches the database.
 */
class CatalogueReader
{
    /** Thrown when the file cannot yield rows at all. */
    public const ERR_EMPTY = 'empty';

    public const ERR_SCANNED_PDF = 'scanned_pdf';

    public const ERR_UNREADABLE = 'unreadable';

    /**
     * Reserved key carrying each row's line number in the source file.
     * Header normalisation strips leading underscores, so no real column can
     * collide with this.
     */
    public const LINE_KEY = '__line';

    /**
     * @return array{rows: array<int, array<string, string>>, notes: array<int, string>}
     *
     * @throws ImportException
     */
    public function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'csv', 'txt' => $this->readCsv($path),
            'pdf' => $this->readPdf($path),
            default => throw new ImportException(
                self::ERR_UNREADABLE,
                'Only CSV and PDF files can be imported. Excel users can "Save As → CSV".'
            ),
        };
    }

    /**
     * @return array{rows: array<int, array<string, string>>, notes: array<int, string>}
     */
    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new ImportException(self::ERR_UNREADABLE, 'The file could not be opened.');
        }

        // Spreadsheets exported from Excel often carry a UTF-8 BOM, which would
        // otherwise become part of the first header name and break the mapping.
        $first = fgets($handle);
        if ($first !== false && str_starts_with($first, "\xEF\xBB\xBF")) {
            $first = substr($first, 3);
        }
        rewind($handle);
        if ($first !== false) {
            fseek($handle, str_starts_with((string) fgets($handle), "\xEF\xBB\xBF") ? 3 : 0);
        }

        $delimiter = $this->sniffDelimiter($first ?: '');

        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');

        if ($header === false || $header === [null]) {
            fclose($handle);

            throw new ImportException(self::ERR_EMPTY, 'The file has no rows.');
        }

        $header = array_map(
            fn ($h) => $this->normaliseHeader((string) $h),
            $header
        );

        $rows = [];
        $lineNumber = 1; // the header

        while (($line = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $lineNumber++;

            // fgetcsv yields [null] for a blank line.
            if ($line === [null] || count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];

            foreach ($header as $i => $name) {
                if ($name === '') {
                    continue;
                }

                $row[$name] = trim((string) ($line[$i] ?? ''));
            }

            // Carried so the review screen can point at the row in the user's
            // own spreadsheet — skipped blank lines would otherwise shift it.
            $row[self::LINE_KEY] = (string) $lineNumber;

            $rows[] = $row;
        }

        fclose($handle);

        if ($rows === []) {
            throw new ImportException(self::ERR_EMPTY, 'The file has a header row but no products under it.');
        }

        return ['rows' => $rows, 'notes' => []];
    }

    /**
     * Pull products out of a PDF that carries a real text layer.
     *
     * Catalogues exported as images (Photoshop "PDF Presentation", scans) hold
     * no text at all — that is detected up front and reported plainly, because
     * the alternative is a silent import of zero products.
     *
     * @return array{rows: array<int, array<string, string>>, notes: array<int, string>}
     */
    protected function readPdf(string $path): array
    {
        try {
            $parser = new PdfParser();
            $text = $parser->parseFile($path)->getText();
        } catch (Throwable $e) {
            throw new ImportException(self::ERR_UNREADABLE, 'This PDF could not be opened: '.$e->getMessage());
        }

        if (trim($text) === '') {
            throw new ImportException(
                self::ERR_SCANNED_PDF,
                'This PDF contains no text — every page is a picture, so there is nothing to read. '
                .'Catalogues exported from Photoshop or scanned from print are always like this. '
                .'Ask your supplier for the product list as CSV or Excel, or enter the products here and export a CSV to reuse later.'
            );
        }

        return $this->rowsFromPdfText($text);
    }

    /**
     * @return array{rows: array<int, array<string, string>>, notes: array<int, string>}
     */
    protected function rowsFromPdfText(string $text): array
    {
        $lines = preg_split('/\R/', $text) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        $rows = [];
        $notes = [];
        $category = '';

        foreach ($lines as $i => $line) {
            if ($this->looksLikeCategoryHeading($line)) {
                $category = $this->titleise($line);

                continue;
            }

            // Product headings read "VALKIN - 1001" or "SHINE - S - 3010": a name
            // in capitals followed by a model code.
            if (! preg_match('/^([A-Z][A-Z0-9 .\'&\/-]{1,48}?)\s*[-–]\s*([A-Z0-9]{2,10})$/u', $line, $m)) {
                continue;
            }

            $name = $this->titleise(trim($m[1]));
            $code = trim($m[2]);

            // Dimensions and trap sizes sit on the next couple of lines.
            $context = implode(' ', array_slice($lines, $i + 1, 3));

            $row = [
                'name' => $name.' '.$code,
                'sku' => $code,
                'category' => $category !== '' ? $category : 'Uncategorised',
                // A PDF has no rows, so the line of text stands in for one.
                self::LINE_KEY => (string) ($i + 1),
            ];

            if (preg_match('/L\s*(\d+)\s*W\s*(\d+)\s*H\s*(\d+)\s*mm/i', $context, $d)) {
                $row['size'] = "L {$d[1]} × W {$d[2]} × H {$d[3]} mm";
            }

            if (preg_match('/([SP])\s*trap\s*[:\-]?\s*([0-9, ]+)mm/i', $context, $t)) {
                $row['trap'] = strtoupper($t[1]).' trap '.trim($t[2]).' mm';
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            throw new ImportException(
                self::ERR_EMPTY,
                'This PDF has text, but no product entries were recognised in it. '
                .'The importer looks for headings such as "VALKIN - 1001". A CSV import will be more reliable here.'
            );
        }

        $notes[] = 'Read from a PDF, so please check the names and sizes below before importing.';

        return ['rows' => $rows, 'notes' => $notes];
    }

    protected function looksLikeCategoryHeading(string $line): bool
    {
        $known = ['CLOSET', 'BASIN', 'URINAL', 'PAN', 'PIPE', 'WIRE', 'CABLE', 'FAN', 'LIGHT', 'PUMP', 'TARPAULIN'];

        if (preg_match('/\d/', $line) || mb_strlen($line) > 46) {
            return false;
        }

        if ($line !== mb_strtoupper($line)) {
            return false;
        }

        foreach ($known as $word) {
            if (str_contains($line, $word)) {
                return true;
            }
        }

        return false;
    }

    /** "ONE PIECE CLOSET" reads better in a catalogue as "One Piece Closet". */
    protected function titleise(string $value): string
    {
        $value = mb_convert_case(mb_strtolower(trim($value)), MB_CASE_TITLE, 'UTF-8');

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    protected function sniffDelimiter(string $line): string
    {
        $counts = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
            '|' => substr_count($line, '|'),
        ];

        arsort($counts);
        $best = array_key_first($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    /** Accepts "Product Name", "product_name" or "PRODUCT NAME" as the same column. */
    protected function normaliseHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }
}
