<?php

namespace SavyApps\LaravelStudio\Services\Media;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SecureMediaUrlService
{
    /**
     * Default expiration time for signed URLs (in minutes).
     */
    protected int $defaultExpiration = 60; // 1 hour

    /**
     * Get a temporary signed URL for a media file.
     *
     * @param  Media  $media  The media model
     * @param  int|null  $expirationMinutes  How long the URL should be valid (default: 60 minutes)
     * @return string The temporary URL
     */
    public function getTemporaryUrl(Media $media, ?int $expirationMinutes = null): string
    {
        $expiration = $expirationMinutes ?? $this->defaultExpiration;

        // Get the disk the media is stored on
        $disk = Storage::disk($media->disk);

        // Check if the disk supports temporary URLs
        if (! method_exists($disk, 'temporaryUrl')) {
            // Fallback to regular URL if disk doesn't support signed URLs
            return $media->getUrl();
        }

        // Generate a temporary signed URL
        return $disk->temporaryUrl(
            $media->getPath(),
            now()->addMinutes($expiration)
        );
    }

    /**
     * Get temporary URLs for multiple media items.
     *
     * @param  Collection<Media>  $mediaCollection  Collection of media models
     * @param  int|null  $expirationMinutes  How long the URLs should be valid
     * @return array Array of media info with temporary URLs
     */
    public function getTemporaryUrls(Collection $mediaCollection, ?int $expirationMinutes = null): array
    {
        return $mediaCollection->map(function (Media $media) use ($expirationMinutes) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $this->getTemporaryUrl($media, $expirationMinutes),
                'mime_type' => $media->mime_type,
                'size' => $media->size,
            ];
        })->toArray();
    }

    /**
     * Get a temporary URL for a specific conversion.
     *
     * @param  Media  $media  The media model
     * @param  string  $conversionName  Name of the conversion
     * @param  int|null  $expirationMinutes  How long the URL should be valid
     * @return string The temporary URL for the conversion
     */
    public function getTemporaryConversionUrl(Media $media, string $conversionName, ?int $expirationMinutes = null): string
    {
        $expiration = $expirationMinutes ?? $this->defaultExpiration;

        // Get the disk the media is stored on
        $disk = Storage::disk($media->disk);

        // Check if the disk supports temporary URLs
        if (! method_exists($disk, 'temporaryUrl')) {
            // Fallback to regular URL if disk doesn't support signed URLs
            return $media->getUrl($conversionName);
        }

        // Get the path for the conversion
        $conversionPath = $media->getPath($conversionName);

        // Generate a temporary signed URL
        return $disk->temporaryUrl(
            $conversionPath,
            now()->addMinutes($expiration)
        );
    }

    /**
     * Set the default expiration time for signed URLs.
     *
     * @param  int  $minutes  Expiration time in minutes
     * @return self
     */
    public function setDefaultExpiration(int $minutes): self
    {
        $this->defaultExpiration = $minutes;

        return $this;
    }
}
