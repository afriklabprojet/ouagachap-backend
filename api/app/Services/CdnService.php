<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gestion centralisée des uploads et des URLs publiques d'assets.
 *
 * En local/staging  : disk 'public'  → /storage/…
 * En production CDN : disk 's3'      → CDN_URL/…
 *
 * Usage :
 *   $url = app(CdnService::class)->upload($request->file('avatar'), 'avatars');
 *   $url = app(CdnService::class)->url('avatars/foo.jpg');
 *   app(CdnService::class)->delete('avatars/foo.jpg');
 */
class CdnService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'public') === 's3' ? 's3' : 'public';
    }

    /**
     * Upload un fichier et retourne l'URL publique CDN (ou locale).
     *
     * @param  UploadedFile  $file
     * @param  string        $folder  ex: 'avatars', 'delivery-photos/2025/05', 'order-chats/42'
     * @param  string|null   $filename  null = nom aléatoire conservant l'extension
     */
    public function upload(UploadedFile $file, string $folder, ?string $filename = null): string
    {
        $name = $filename
            ? $filename . '.' . $file->getClientOriginalExtension()
            : Str::uuid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $name, [
            'disk'       => $this->disk,
            'visibility' => 'public',
        ]);

        return $this->url($path);
    }

    /**
     * Retourne l'URL publique d'un chemin stocké.
     *
     * Priorité pour S3/R2 :
     *   1. CDN_URL défini (domaine public custom ou r2.dev) → CDN_URL/path
     *   2. CDN_URL vide + endpoint R2 → endpoint/bucket/path (bucket public activé)
     *   3. Fallback → URL générée par le SDK Flysystem
     */
    public function url(string $path): string
    {
        if ($this->disk === 's3') {
            $cdnUrl = rtrim(config('filesystems.disks.s3.url', ''), '/');
            if ($cdnUrl) {
                return $cdnUrl . '/' . ltrim($path, '/');
            }

            // R2 sans domaine public custom : endpoint + bucket + path
            $endpoint = rtrim(config('filesystems.disks.s3.endpoint', ''), '/');
            $bucket   = config('filesystems.disks.s3.bucket', '');
            if ($endpoint && $bucket) {
                return $endpoint . '/' . $bucket . '/' . ltrim($path, '/');
            }

            return Storage::disk('s3')->url($path);
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Supprime un fichier du disk actif.
     */
    public function delete(string $path): void
    {
        Storage::disk($this->disk)->delete($path);
    }

    /**
     * Extrait le chemin relatif depuis une URL complète (utile pour la suppression).
     */
    public function pathFromUrl(string $url): string
    {
        // Essayer dans l'ordre : CDN_URL, endpoint+bucket, /storage local
        $candidates = array_filter([
            rtrim(config('filesystems.disks.s3.url', ''), '/'),
            (function () {
                $ep = rtrim(config('filesystems.disks.s3.endpoint', ''), '/');
                $bk = config('filesystems.disks.s3.bucket', '');
                return ($ep && $bk) ? "{$ep}/{$bk}" : '';
            })(),
            rtrim(url('/storage'), '/'),
        ]);

        foreach ($candidates as $base) {
            if ($base && str_starts_with($url, $base)) {
                return ltrim(Str::after($url, $base), '/');
            }
        }

        return ltrim(parse_url($url, PHP_URL_PATH) ?? $url, '/');
    }
}
