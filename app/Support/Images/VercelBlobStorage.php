<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Admin-uploaded product photos need to survive across serverless
 * invocations and deploys, which local disk storage can't do on Vercel
 * (storage/ resolves to /tmp, wiped per invocation — see bootstrap/app.php).
 * Vercel Blob is the persistent, publicly-served store for these. There's
 * no official Laravel filesystem driver for it, so this talks to its REST
 * API directly.
 */
class VercelBlobStorage
{
    protected const API = 'https://blob.vercel-storage.com';

    public static function isConfigured(): bool
    {
        return filled(env('BLOB_READ_WRITE_TOKEN'));
    }

    public static function put(UploadedFile $file, string $directory): string
    {
        $pathname = trim($directory, '/').'/'.$file->hashName();

        $response = Http::withToken(env('BLOB_READ_WRITE_TOKEN'))
            ->withHeaders([
                'x-api-version' => '7',
                'x-content-type' => $file->getMimeType(),
                'x-add-random-suffix' => '1',
            ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
            ->put(self::API.'/'.$pathname);

        if ($response->failed()) {
            throw new RuntimeException('Vercel Blob upload failed: '.$response->body());
        }

        $url = $response->json('url');

        if (! $url) {
            throw new RuntimeException('Vercel Blob upload returned no URL: '.$response->body());
        }

        return $url;
    }

    public static function delete(string $url): void
    {
        Http::withToken(env('BLOB_READ_WRITE_TOKEN'))
            ->withHeaders(['x-api-version' => '7'])
            ->post(self::API.'/delete', ['urls' => [$url]]);
    }
}
