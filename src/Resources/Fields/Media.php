<?php

namespace SavyApps\LaravelStudio\Resources\Fields;

class Media extends Field
{
    protected bool $multiple = false;

    protected string $collection = 'default';

    protected array $acceptedTypes = ['image/*'];

    /**
     * Allowed file extensions for security.
     * Empty array means no restriction (rely on MIME type only).
     */
    protected array $allowedExtensions = [];

    /**
     * Dangerous extensions that should never be allowed.
     */
    protected static array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
        'exe', 'bat', 'cmd', 'com', 'msi', 'dll', 'scr',
        'js', 'jse', 'vbs', 'vbe', 'wsf', 'wsh',
        'sh', 'bash', 'csh', 'ksh', 'pl', 'py', 'rb',
        'htaccess', 'htpasswd', 'ini', 'config',
        'asp', 'aspx', 'asa', 'asax', 'ascx', 'ashx', 'asmx',
        'jsp', 'jspx', 'cfm', 'cfc',
        'svg', // SVG can contain JavaScript
    ];

    protected ?int $maxFiles = null;

    protected ?int $maxFileSize = null; // in MB

    protected ?int $previewWidth = null;

    protected ?int $previewHeight = null;

    protected bool $rounded = false;

    protected ?string $disk = null;

    protected bool $editable = false;

    protected array $editorOptions = [];

    protected function fieldType(): string
    {
        return 'media';
    }

    /**
     * Enable single file mode (default).
     */
    public function single(): static
    {
        $this->multiple = false;
        $this->maxFiles = 1;
        $this->meta(['multiple' => false, 'maxFiles' => 1]);

        return $this;
    }

    /**
     * Enable multiple files mode.
     */
    public function multiple(?int $maxFiles = null): static
    {
        $this->multiple = true;
        $this->maxFiles = $maxFiles;
        $this->meta(['multiple' => true, 'maxFiles' => $maxFiles]);

        return $this;
    }

    /**
     * Set the media collection name.
     */
    public function collection(string $collection): static
    {
        $this->collection = $collection;
        $this->meta(['collection' => $collection]);

        return $this;
    }

    /**
     * Set accepted file types.
     */
    public function acceptedTypes(array $types): static
    {
        $this->acceptedTypes = $types;
        $this->meta(['acceptedTypes' => $types]);

        return $this;
    }

    /**
     * Accept only images.
     */
    public function images(): static
    {
        $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        return $this->acceptedTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Accept images including SVG (use with caution).
     */
    public function imagesWithSvg(): static
    {
        $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        return $this->acceptedTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);
    }

    /**
     * Accept only documents.
     */
    public function documents(): static
    {
        $this->allowedExtensions = ['pdf', 'doc', 'docx'];

        return $this->acceptedTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    /**
     * Set allowed file extensions (whitelist).
     */
    public function allowedExtensions(array $extensions): static
    {
        // Filter out any dangerous extensions
        $this->allowedExtensions = array_filter(
            array_map('strtolower', $extensions),
            fn ($ext) => !in_array(strtolower($ext), static::$dangerousExtensions)
        );
        $this->meta(['allowedExtensions' => $this->allowedExtensions]);

        return $this;
    }

    /**
     * Check if a file extension is allowed.
     */
    public function isExtensionAllowed(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Always reject dangerous extensions
        if (in_array($extension, static::$dangerousExtensions)) {
            return false;
        }

        // If no whitelist specified, allow (except dangerous)
        if (empty($this->allowedExtensions)) {
            return true;
        }

        return in_array($extension, $this->allowedExtensions);
    }

    /**
     * Get dangerous extensions list.
     */
    public static function getDangerousExtensions(): array
    {
        return static::$dangerousExtensions;
    }

    /**
     * Set maximum file size in MB.
     */
    public function maxFileSize(int $mb): static
    {
        $this->maxFileSize = $mb;
        $this->meta(['maxFileSize' => $mb]);

        return $this;
    }

    /**
     * Set preview dimensions.
     */
    public function previewSize(int $width, int $height): static
    {
        $this->previewWidth = $width;
        $this->previewHeight = $height;
        $this->meta(['previewWidth' => $width, 'previewHeight' => $height]);

        return $this;
    }

    /**
     * Make preview images rounded (circular).
     */
    public function rounded(bool $rounded = true): static
    {
        $this->rounded = $rounded;
        $this->meta(['rounded' => $rounded]);

        return $this;
    }

    /**
     * Set the disk to store media on.
     */
    public function disk(string $disk): static
    {
        $this->disk = $disk;
        $this->meta(['disk' => $disk]);

        return $this;
    }

    /**
     * Enable image editing before upload.
     */
    public function editable(array $options = []): static
    {
        $this->editable = true;
        $this->editorOptions = $options;
        $this->meta([
            'editable' => true,
            'editorOptions' => $options,
        ]);

        return $this;
    }

    /**
     * Transform value to include media URLs.
     */
    public function transformValue(mixed $value, $model): mixed
    {
        // Check if model has the getMedia method (Spatie Media Library trait)
        if (! $model || ! method_exists($model, 'getMedia')) {
            return null;
        }

        try {
            $media = $model->getMedia($this->collection);

            if ($this->multiple) {
                $items = $media->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->file_name,
                        'url' => $item->getUrl(),
                        'thumbnail' => $item->hasGeneratedConversion('thumb') ? $item->getUrl('thumb') : $item->getUrl(),
                        'size' => $item->size,
                        'mime_type' => $item->mime_type,
                    ];
                })->toArray();

                return empty($items) ? null : $items;
            }

            $firstMedia = $media->first();

            if (! $firstMedia) {
                return null;
            }

            return [
                'id' => $firstMedia->id,
                'name' => $firstMedia->file_name,
                'url' => $firstMedia->getUrl(),
                'thumbnail' => $firstMedia->hasGeneratedConversion('thumb') ? $firstMedia->getUrl('thumb') : $firstMedia->getUrl(),
                'size' => $firstMedia->size,
                'mime_type' => $firstMedia->mime_type,
            ];
        } catch (\Exception $e) {
            // Log error but return null to prevent breaking the API
            \Log::error('Media transform error: '.$e->getMessage());

            return null;
        }
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'multiple' => $this->multiple,
            'collection' => $this->collection,
            'acceptedTypes' => $this->acceptedTypes,
            'allowedExtensions' => $this->allowedExtensions,
            'maxFiles' => $this->maxFiles,
            'maxFileSize' => $this->maxFileSize,
            'previewWidth' => $this->previewWidth,
            'previewHeight' => $this->previewHeight,
            'rounded' => $this->rounded,
            'disk' => $this->disk,
            'editable' => $this->editable,
            'editorOptions' => $this->editorOptions,
        ]);
    }
}
