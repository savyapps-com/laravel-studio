<?php

namespace SavyApps\LaravelStudio\Cards;

use Closure;

class ChartCard extends Card
{
    /**
     * The component name.
     */
    protected string $component = 'chart-card';

    /**
     * The data callback or static value.
     */
    protected mixed $data = null;

    /**
     * The chart type (line, bar, area).
     */
    protected string $chartType = 'line';

    /**
     * X-axis field name.
     */
    protected string $xAxis = 'date';

    /**
     * Y-axis field names (supports multiple series).
     */
    protected array $yAxis = ['value'];

    /**
     * Series labels for multiple y-axis fields.
     */
    protected array $seriesLabels = [];

    /**
     * Series colors.
     */
    protected array $seriesColors = [];

    /**
     * Default color palette.
     */
    protected array $defaultColors = [
        'blue', 'green', 'yellow', 'red', 'purple',
        'pink', 'indigo', 'cyan', 'orange', 'teal'
    ];

    /**
     * Chart height in pixels.
     */
    protected int $chartHeight = 300;

    /**
     * Whether to show the legend.
     */
    protected bool $showLegend = true;

    /**
     * Whether to show grid lines.
     */
    protected bool $showGrid = true;

    /**
     * Whether to fill the area under the line.
     */
    protected bool $fill = false;

    /**
     * Whether to smooth the line.
     */
    protected bool $smooth = true;

    /**
     * Y-axis format.
     */
    protected string $yAxisFormat = 'number';

    /**
     * Currency code for currency format.
     */
    protected string $currency = 'USD';

    /**
     * Get the card type.
     */
    public function type(): string
    {
        return 'chart';
    }

    /**
     * Set the chart data.
     */
    public function data(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set the chart type.
     */
    public function chartType(string $type): static
    {
        $this->chartType = $type;
        return $this;
    }

    /**
     * Set as line chart.
     */
    public function line(): static
    {
        $this->chartType = 'line';
        return $this;
    }

    /**
     * Set as bar chart.
     */
    public function bar(): static
    {
        $this->chartType = 'bar';
        return $this;
    }

    /**
     * Set as area chart.
     */
    public function area(): static
    {
        $this->chartType = 'area';
        $this->fill = true;
        return $this;
    }

    /**
     * Set the x-axis field.
     */
    public function xAxis(string $field): static
    {
        $this->xAxis = $field;
        return $this;
    }

    /**
     * Set the y-axis field(s).
     */
    public function yAxis(string|array $fields): static
    {
        $this->yAxis = is_array($fields) ? $fields : [$fields];
        return $this;
    }

    /**
     * Set series labels.
     */
    public function seriesLabels(array $labels): static
    {
        $this->seriesLabels = $labels;
        return $this;
    }

    /**
     * Set series colors.
     */
    public function seriesColors(array $colors): static
    {
        $this->seriesColors = $colors;
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
     * Hide the legend.
     */
    public function withoutLegend(): static
    {
        $this->showLegend = false;
        return $this;
    }

    /**
     * Hide grid lines.
     */
    public function withoutGrid(): static
    {
        $this->showGrid = false;
        return $this;
    }

    /**
     * Fill the area under the line.
     */
    public function filled(): static
    {
        $this->fill = true;
        return $this;
    }

    /**
     * Disable line smoothing.
     */
    public function straight(): static
    {
        $this->smooth = false;
        return $this;
    }

    /**
     * Set y-axis format.
     */
    public function yAxisFormat(string $format, ?string $currency = null): static
    {
        $this->yAxisFormat = $format;
        if ($currency) {
            $this->currency = $currency;
        }
        return $this;
    }

    /**
     * Calculate the card's value.
     */
    public function calculate(): mixed
    {
        $data = $this->data instanceof Closure
            ? call_user_func($this->data)
            : $this->data;

        if (!is_array($data)) {
            $data = [];
        }

        // Extract labels (x-axis values)
        $labels = array_column($data, $this->xAxis);

        // Build series data
        $series = [];
        foreach ($this->yAxis as $index => $field) {
            $values = array_column($data, $field);
            $label = $this->seriesLabels[$field] ?? $this->seriesLabels[$index] ?? ucfirst(str_replace('_', ' ', $field));
            $color = $this->seriesColors[$field] ?? $this->seriesColors[$index] ?? $this->defaultColors[$index % count($this->defaultColors)];

            $series[] = [
                'key' => $field,
                'label' => $label,
                'color' => $color,
                'data' => $values,
            ];
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'raw' => $data,
        ];
    }

    /**
     * Get additional data for this card type.
     */
    protected function additionalData(): array
    {
        return [
            'chartType' => $this->chartType,
            'chartHeight' => $this->chartHeight,
            'showLegend' => $this->showLegend,
            'showGrid' => $this->showGrid,
            'fill' => $this->fill,
            'smooth' => $this->smooth,
            'yAxisFormat' => $this->yAxisFormat,
            'currency' => $this->currency,
        ];
    }
}
