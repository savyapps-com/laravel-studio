<?php

namespace SavyApps\LaravelStudio\Cards;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class Card implements Arrayable, JsonSerializable
{
    /**
     * The card's title.
     */
    protected string $title;

    /**
     * The card's icon.
     */
    protected ?string $icon = null;

    /**
     * The card's color.
     */
    protected string $color = 'blue';

    /**
     * The card's width (full, 1/2, 1/3, 1/4).
     */
    protected string $width = '1/4';

    /**
     * Auto-refresh interval in seconds.
     */
    protected ?int $refreshInterval = null;

    /**
     * Whether to enable caching.
     */
    protected bool $cacheable = true;

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 300;

    /**
     * Additional CSS classes.
     */
    protected ?string $cssClass = null;

    /**
     * The component name for frontend rendering.
     */
    protected string $component;

    /**
     * Help text for the card.
     */
    protected ?string $helpText = null;

    /**
     * Link URL when card is clicked.
     */
    protected ?string $link = null;

    /**
     * Whether the card is visible.
     */
    protected bool|Closure $visible = true;

    /**
     * Create a new card instance.
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * Create a new card instance.
     */
    public static function make(string $title): static
    {
        return new static($title);
    }

    /**
     * Set the card's icon.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set the card's color.
     */
    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Set the card's width.
     */
    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Set auto-refresh interval in seconds.
     */
    public function refreshEvery(int $seconds): static
    {
        $this->refreshInterval = $seconds;
        return $this;
    }

    /**
     * Disable caching for this card.
     */
    public function withoutCaching(): static
    {
        $this->cacheable = false;
        return $this;
    }

    /**
     * Set cache TTL in seconds.
     */
    public function cacheTtl(int $seconds): static
    {
        $this->cacheTtl = $seconds;
        return $this;
    }

    /**
     * Set additional CSS classes.
     */
    public function cssClass(string $class): static
    {
        $this->cssClass = $class;
        return $this;
    }

    /**
     * Set help text for the card.
     */
    public function helpText(string $text): static
    {
        $this->helpText = $text;
        return $this;
    }

    /**
     * Set a link URL for the card.
     */
    public function link(string $url): static
    {
        $this->link = $url;
        return $this;
    }

    /**
     * Set visibility condition.
     */
    public function canSee(bool|Closure $callback): static
    {
        $this->visible = $callback;
        return $this;
    }

    /**
     * Determine if the card should be visible.
     */
    public function isVisible(): bool
    {
        if ($this->visible instanceof Closure) {
            return call_user_func($this->visible, request()->user());
        }

        return $this->visible;
    }

    /**
     * Get the card type.
     */
    abstract public function type(): string;

    /**
     * Calculate the card's value/data.
     */
    abstract public function calculate(): mixed;

    /**
     * Get the unique key for this card.
     */
    public function key(): string
    {
        return md5(static::class . $this->title);
    }

    /**
     * Resolve the card's value with caching.
     */
    public function resolve(): mixed
    {
        if (!$this->cacheable) {
            return $this->calculate();
        }

        $cacheKey = 'studio_card_' . $this->key();

        return cache()->remember($cacheKey, $this->cacheTtl, function () {
            return $this->calculate();
        });
    }

    /**
     * Get the card's component name.
     */
    public function getComponent(): string
    {
        return $this->component ?? $this->type() . '-card';
    }

    /**
     * Get base card data for serialization.
     */
    protected function baseData(): array
    {
        return [
            'key' => $this->key(),
            'type' => $this->type(),
            'component' => $this->getComponent(),
            'title' => $this->title,
            'icon' => $this->icon,
            'color' => $this->color,
            'width' => $this->width,
            'refreshInterval' => $this->refreshInterval,
            'cssClass' => $this->cssClass,
            'helpText' => $this->helpText,
            'link' => $this->link,
        ];
    }

    /**
     * Get additional data specific to the card type.
     */
    protected function additionalData(): array
    {
        return [];
    }

    /**
     * Convert the card to an array.
     */
    public function toArray(): array
    {
        return array_merge(
            $this->baseData(),
            $this->additionalData(),
            ['data' => $this->resolve()]
        );
    }

    /**
     * Convert the card to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
