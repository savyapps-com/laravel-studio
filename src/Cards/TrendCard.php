<?php

namespace SavyApps\LaravelStudio\Cards;

use Closure;

class TrendCard extends Card
{
    /**
     * The component name.
     */
    protected string $component = 'trend-card';

    /**
     * The current value callback or static value.
     */
    protected mixed $value = null;

    /**
     * The previous value for comparison.
     */
    protected mixed $previousValue = null;

    /**
     * The value format.
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
     * Comparison label (e.g., "vs last week").
     */
    protected ?string $comparisonLabel = null;

    /**
     * Whether to show the trend chart.
     */
    protected bool $showChart = true;

    /**
     * Trend data points for the chart.
     */
    protected mixed $trendData = null;

    /**
     * Chart height in pixels.
     */
    protected int $chartHeight = 60;

    /**
     * Get the card type.
     */
    public function type(): string
    {
        return 'trend';
    }

    /**
     * Set the current value.
     */
    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set the previous value for comparison.
     */
    public function previousValue(mixed $value): static
    {
        $this->previousValue = $value;
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
     * Set the comparison label.
     */
    public function comparisonLabel(string $label): static
    {
        $this->comparisonLabel = $label;
        return $this;
    }

    /**
     * Hide the trend chart.
     */
    public function withoutChart(): static
    {
        $this->showChart = false;
        return $this;
    }

    /**
     * Set the trend data points.
     */
    public function trendData(mixed $data): static
    {
        $this->trendData = $data;
        return $this;
    }

    /**
     * Set the chart height.
     */
    public function chartHeight(int $height): static
    {
        $this->chartHeight = $height;
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

        $trendData = $this->trendData instanceof Closure
            ? call_user_func($this->trendData)
            : $this->trendData;

        $change = null;
        $changePercentage = null;
        $trend = null;

        if ($previousValue !== null && $previousValue != 0) {
            $change = $value - $previousValue;
            $changePercentage = round(($change / $previousValue) * 100, 1);
            $trend = $change >= 0 ? 'up' : 'down';
        } elseif ($previousValue === 0 && $value > 0) {
            $trend = 'up';
            $changePercentage = 100;
            $change = $value;
        } elseif ($previousValue !== null) {
            $trend = 'neutral';
            $change = 0;
            $changePercentage = 0;
        }

        return [
            'value' => $value,
            'previousValue' => $previousValue,
            'change' => $change,
            'changePercentage' => $changePercentage,
            'trend' => $trend,
            'trendData' => $trendData,
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
            'comparisonLabel' => $this->comparisonLabel,
            'showChart' => $this->showChart,
            'chartHeight' => $this->chartHeight,
        ];
    }
}
