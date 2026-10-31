<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisualBoardImageStorage
{
    private const CLOUDINARY_PREFIX = 'cld:';
    private const CLOUDINARY_FOLDER = 'afterglow_visual_boards';

    public function store(UploadedFile $file): string
    {
        if (config('afterglow_visual_storage.driver', 'public') !== 'cloudinary') {
            $path = $file->store('visual-boards', 'public');

            if ($path === false) {
                throw new RuntimeException('Unable to save your image.');
            }

            return $path;
        }

        [$cloudName, $apiKey, $apiSecret] = $this->credentials();
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Unable to read the uploaded image.');
        }

        // A signed-in user uploads via the Laravel backend; API Secret stays server-side.
        // The original filename is never sent to the CDN.
        $response = Http::withBasicAuth($apiKey, $apiSecret)
            ->timeout(45)
            ->attach('file', $contents, Str::uuid().'.'.$file->guessExtension())
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                'folder' => self::CLOUDINARY_FOLDER,
            ]);

        if (! $response->successful()) {
            Log::warning('Cloudinary artwork upload failed', [
                'http_status' => $response->status(),
                'error' => $response->json('error.message'),
            ]);
            throw new RuntimeException('The image hosting service could not save your image.');
        }

        $publicId = $response->json('public_id');
        $format = $response->json('format');

        if (! is_string($publicId) || $publicId === '' ||
            ! in_array($format, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw new RuntimeException('Unexpected response from the image hosting service.');
        }

        $reference = self::CLOUDINARY_PREFIX.$format.':'.$publicId;
        if (strlen($reference) > 255) {
            throw new RuntimeException('The image identifier is too long to save.');
        }

        return $reference;
    }

    public function respond(string $path): StreamedResponse|RedirectResponse
    {
        $remote = $this->parseRemotePath($path);

        if ($remote !== null) {
            [$format, $publicId] = $remote;
            $cloudName = (string) config('afterglow_visual_storage.cloud_name');
            abort_if($cloudName === '', 503, 'Artwork hosting is not configured.');

            $segments = array_map('rawurlencode', explode('/', $publicId));
            $url = 'https://res.cloudinary.com/'.rawurlencode($cloudName)
                .'/image/upload/'.implode('/', $segments).'.'.$format;

            return redirect()->away($url, 302, [
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(string $path): void
    {
        $remote = $this->parseRemotePath($path);

        if ($remote === null) {
            Storage::disk('public')->delete($path);
            return;
        }

        [, $publicId] = $remote;
        [$cloudName, $apiKey, $apiSecret] = $this->credentials();

        try {
            $response = Http::withBasicAuth($apiKey, $apiSecret)
                ->asForm()
                ->timeout(30)
                ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
                    'public_id' => $publicId,
                    'invalidate' => 'true',
                ]);

            if (! $response->successful() ||
                ! in_array($response->json('result'), ['ok', 'not found'], true)) {
                Log::warning('Cloudinary artwork deletion was not confirmed', [
                    'http_status' => $response->status(),
                    'public_id' => $publicId,
                ]);
            }
        } catch (\Throwable $e) {
            // The user-facing database operation has already completed. Log failures
            // for later cleanup without exposing cloud credentials or internal errors.
            Log::warning('Could not delete Cloudinary artwork', [
                'public_id' => $publicId,
                'exception' => get_class($e),
            ]);
        }
    }

    private function parseRemotePath(string $path): ?array
    {
        if (! str_starts_with($path, self::CLOUDINARY_PREFIX)) {
            return null;
        }

        $parts = explode(':', $path, 3);
        if (count($parts) !== 3 ||
            ! in_array($parts[1], ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ||
            ! preg_match('~^[A-Za-z0-9_./-]+$~D', $parts[2])) {
            throw new RuntimeException('Invalid stored image reference.');
        }

        return [$parts[1], $parts[2]];
    }

    private function credentials(): array
    {
        $cloudName = (string) config('afterglow_visual_storage.cloud_name');
        $apiKey = (string) config('afterglow_visual_storage.api_key');
        $apiSecret = (string) config('afterglow_visual_storage.api_secret');

        if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
            throw new RuntimeException('Cloudinary settings are incomplete on the server.');
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/D', $cloudName)) {
            throw new RuntimeException('Invalid Cloudinary cloud name.');
        }

        return [$cloudName, $apiKey, $apiSecret];
    }
}
