<?php

namespace SavyApps\LaravelStudio\Cards;

use Closure;

class ValueCard extends Card
{
    /**
     * The component name.
     */
    protected string $component = 'value-card';

    /**
     * The value callback or static value.
     */
    protected mixed $value = null;

    /**
     * The value format (number, currency, percentage).
     */
    protected string $format = 'number';

    /**
     * Currency code for currency format.
     */
    protected string $currency = 'USD';

    /**
     * Number of decimal places.
     */
    protected int $decimals = 0;

    /**
     * Prefix to display before the value.
     */
    protected ?string $prefix = null;

    /**
     * Suffix to display after the value.
     */
    protected ?string $suffix = null;

    /**
     * Previous value for trend calculation.
     */
    protected mixed $previousValue = null;

    /**
     * Trend direction text (e.g., "vs last month").
     */
    protected ?string $trendLabel = null;

    /**
     * Get the card type.
     */
    public function type(): string
    {
        return 'value';
    }

    /**
     * Set the card's value.
     */
    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set the value format.
     */
    public function format(string $format, ?string $currency = null): static
    {
        $this->format = $format;
        if ($currency) {
            $this->currency = $currency;
        }
        return $this;
    }

    /**
     * Set as currency format.
     */
    public function currency(string $currency = 'USD'): static
    {
        $this->format = 'currency';
        $this->currency = $currency;
        return $this;
    }

    /**
     * Set as percentage format.
     */
    public function percentage(): static
    {
        $this->format = 'percentage';
        return $this;
    }

    /**
     * Set number of decimal places.
     */
    public function decimals(int $decimals): static
    {
        $this->decimals = $decimals;
        return $this;
    }

    /**
     * Set a prefix for the value.
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set a suffix for the value.
     */
    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Set the previous value for trend calculation.
     */
    public function previousValue(mixed $value): static
    {
        $this->previousValue = $value;
        return $this;
    }

    /**
     * Set the trend label.
     */
    public function trendLabel(string $label): static
    {
        $this->trendLabel = $label;
        return $this;
    }

    /**
     * Calculate the card's value.
     */
    public function calculate(): mixed
    {
        $value = $this->value instanceof Closure
            ? call_user_func($this->value)
            : $this->value;

        $previousValue = $this->previousValue instanceof Closure
            ? call_user_func($this->previousValue)
            : $this->previousValue;

        $trend = null;
        $trendPercentage = null;

        if ($previousValue !== null && $previousValue != 0) {
            $change = $value - $previousValue;
            $trendPercentage = round(($change / $previousValue) * 100, 1);
            $trend = $change >= 0 ? 'up' : 'down';
        } elseif ($previousValue === 0 && $value > 0) {
            $trend = 'up';
            $trendPercentage = 100;
        }

        return [
            'value' => $value,
            'previousValue' => $previousValue,
            'trend' => $trend,
            'trendPercentage' => $trendPercentage,
        ];
    }

    /**
     * Get additional data for this card type.
     */
    protected function additionalData(): array
    {
        return [
            'format' => $this->format,
            'currency' => $this->currency,
            'decimals' => $this->decimals,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'trendLabel' => $this->trendLabel,
        ];
    }
}
