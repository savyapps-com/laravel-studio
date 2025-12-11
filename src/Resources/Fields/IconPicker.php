<?php

namespace SavyApps\LaravelStudio\Resources\Fields;

class IconPicker extends Field
{
    protected array $icons = [];

    protected bool $searchable = true;

    protected function fieldType(): string
    {
        return 'icon-picker';
    }

    /**
     * Set custom icons to display (overrides default icons).
     */
    public function icons(array $icons): static
    {
        $this->icons = $icons;

        return $this->meta(['icons' => $icons]);
    }

    /**
     * Whether the icon picker should have search functionality.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this->meta(['searchable' => $searchable]);
    }

    /**
     * Set columns for the icon grid display.
     */
    public function columns(int $columns): static
    {
        return $this->meta(['columns' => $columns]);
    }

    /**
     * Set the size of icon previews in the picker.
     */
    public function previewSize(string $size): static
    {
        return $this->meta(['previewSize' => $size]);
    }

    /**
     * Get the default icons available in the package.
     */
    public static function defaultIcons(): array
    {
        return [
            // Navigation & UI
            'dashboard', 'menu', 'close', 'search', 'home', 'settings', 'cog',
            'chevron-down', 'chevron-up', 'chevron-right', 'chevron-left',
            'arrow-left', 'arrow-right', 'arrow-up', 'arrow-down', 'arrow-path',

            // Layout
            'layout', 'layout-sidebar', 'layout-navbar', 'layout-sidebar-right',
            'layout-sidebar-left', 'grid', 'list', 'table', 'squares-2x2',

            // Users & Security
            'user', 'users', 'user-plus', 'user-minus', 'user-check', 'team', 'profile',
            'shield', 'shield-exclamation', 'lock', 'unlock', 'key', 'logout',

            // Status & Alerts
            'check', 'check-circle', 'check-circle-filled', 'x-circle', 'x-mark', 'close',
            'alert-circle', 'alert-triangle', 'info-circle', 'help-circle', 'question',
            'badge-check',

            // Communication
            'mail', 'messages', 'chat', 'bell', 'inbox', 'paper-airplane', 'phone',

            // Data & Content
            'analytics', 'reports', 'tasks', 'clipboard', 'document-text', 'document-duplicate',
            'file', 'folder', 'folder-open', 'folder-plus', 'archive', 'newspaper',

            // Actions
            'edit', 'pencil', 'delete', 'trash', 'add', 'plus', 'minus',
            'save', 'download', 'upload', 'copy', 'duplicate', 'share',
            'refresh', 'undo', 'redo', 'eraser',

            // Media
            'image', 'video', 'camera', 'microphone', 'music-note', 'film', 'play', 'pause',
            'rotate-left', 'rotate-right', 'flip-horizontal', 'flip-vertical',
            'zoom-in', 'zoom-out', 'maximize', 'minimize',

            // Visual
            'eye', 'eye-off', 'eye-slash', 'sun', 'moon', 'palette', 'adjustments',

            // Commerce
            'shopping-cart', 'credit-card', 'currency-dollar', 'receipt', 'tag',

            // Favorites & Ratings
            'star', 'star-filled', 'heart', 'bookmark', 'bookmark-filled', 'flag',
            'thumbs-up', 'thumbs-down', 'pin',

            // Charts & Data Visualization
            'chart-bar', 'chart-line', 'chart-pie',

            // Tech & Development
            'code', 'code-2', 'code-bracket', 'globe', 'server', 'database',
            'cube-transparent', 'monitor', 'device-mobile',

            // Links & External
            'link', 'external-link', 'paperclip', 'at-symbol', 'hashtag',

            // Text Formatting
            'bold', 'italic', 'underline', 'strikethrough',
            'list-ordered', 'list-checks', 'align-left', 'align-center', 'align-right', 'quote',

            // Time & Calendar
            'clock', 'calendar',

            // Misc
            'more-vertical', 'dots-horizontal', 'bars-3', 'filter', 'sort',
            'lightning-bolt', 'squares-plus', 'cog-6-tooth',
            'arrows-pointing-out', 'arrows-pointing-in', 'circle', 'loading',
        ];
    }
}
