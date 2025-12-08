<?php

namespace SavyApps\LaravelStudio\Cards;

use Closure;

class PartitionCard extends Card
{
    /**
     * The component name.
     */
    protected string $component = 'partition-card';

    /**
     * The data callback or static value.
     */
    protected mixed $data = null;

    /**
     * The chart type (donut, pie, bar).
     */
    protected string $chartType = 'donut';

    /**
     * Colors for each partition.
     */
    protected array $colors = [];

    /**
     * Default color palette.
     */
    protected array $defaultColors = [
        'blue', 'green', 'yellow', 'red', 'purple',
        'pink', 'indigo', 'cyan', 'orange', 'teal'
    ];

    /**
     * Whether to show values in the legend.
     */
    protected bool $showValues = true;

    /**
     * Whether to show percentages in the legend.
     */
    protected bool $showPercentages = true;

    /**
     * Maximum items to show (rest grouped as "Other").
     */
    protected ?int $maxItems = null;

    /**
     * Chart height in pixels.
     */
    protected int $chartHeight = 200;

    /**
     * Get the card type.
     */
    public function type(): string
    {
        return 'partition';
    }

    /**
     * Set the partition data.
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
     * Set as donut chart.
     */
    public function donut(): static
    {
        $this->chartType = 'donut';
        return $this;
    }

    /**
     * Set as pie chart.
     */
    public function pie(): static
    {
        $this->chartType = 'pie';
        return $this;
    }

    /**
     * Set as horizontal bar chart.
     */
    public function bar(): static
    {
        $this->chartType = 'bar';
        return $this;
    }

    /**
     * Set colors for each partition.
     */
    public function colors(array $colors): static
    {
        $this->colors = $colors;
        return $this;
    }

    /**
     * Hide values in the legend.
     */
    public function withoutValues(): static
    {
        $this->showValues = false;
        return $this;
    }

    /**
     * Hide percentages in the legend.
     */
    public function withoutPercentages(): static
    {
        $this->showPercentages = false;
        return $this;
    }

    /**
     * Limit the number of items shown.
     */
    public function maxItems(int $max): static
    {
        $this->maxItems = $max;
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
        $data = $this->data instanceof Closure
            ? call_user_func($this->data)
            : $this->data;

        if (!is_array($data)) {
            $data = [];
        }

        $total = array_sum($data);
        $partitions = [];
        $index = 0;

        // Sort by value descending
        arsort($data);

        // Limit items if needed
        if ($this->maxItems && count($data) > $this->maxItems) {
            $main = array_slice($data, 0, $this->maxItems - 1, true);
            $other = array_slice($data, $this->maxItems - 1, null, true);
            $data = $main;
            $data['Other'] = array_sum($other);
        }

        foreach ($data as $label => $value) {
            $color = $this->colors[$label] ?? $this->defaultColors[$index % count($this->defaultColors)];
            $percentage = $total > 0 ? round(($value / $total) * 100, 1) : 0;

            $partitions[] = [
                'label' => $label,
                'value' => $value,
                'percentage' => $percentage,
                'color' => $color,
            ];

            $index++;
        }

        return [
            'partitions' => $partitions,
            'total' => $total,
        ];
    }

    /**
     * Get additional data for this card type.
     */
    protected function additionalData(): array
    {
        return [
            'chartType' => $this->chartType,
            'showValues' => $this->showValues,
            'showPercentages' => $this->showPercentages,
            'chartHeight' => $this->chartHeight,
        ];
    }
}
