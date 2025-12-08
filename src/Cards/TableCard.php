<?php

namespace SavyApps\LaravelStudio\Cards;

use Closure;
use Illuminate\Database\Eloquent\Collection;

class TableCard extends Card
{
    /**
     * The component name.
     */
    protected string $component = 'table-card';

    /**
     * The data callback or static value.
     */
    protected mixed $data = null;

    /**
     * The columns to display.
     */
    protected array $columns = [];

    /**
     * Maximum rows to display.
     */
    protected int $limit = 5;

    /**
     * Whether to show column headers.
     */
    protected bool $showHeaders = true;

    /**
     * Row click handler URL pattern.
     */
    protected ?string $rowUrlPattern = null;

    /**
     * Custom row transformers.
     */
    protected ?Closure $rowTransformer = null;

    /**
     * Empty state message.
     */
    protected string $emptyMessage = 'No data available';

    /**
     * Get the card type.
     */
    public function type(): string
    {
        return 'table';
    }

    /**
     * Set the table data.
     */
    public function data(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set the columns to display.
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set the maximum rows.
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Hide column headers.
     */
    public function withoutHeaders(): static
    {
        $this->showHeaders = false;
        return $this;
    }

    /**
     * Set the row URL pattern.
     */
    public function rowUrl(string $pattern): static
    {
        $this->rowUrlPattern = $pattern;
        return $this;
    }

    /**
     * Set a custom row transformer.
     */
    public function transformRow(Closure $callback): static
    {
        $this->rowTransformer = $callback;
        return $this;
    }

    /**
     * Set the empty state message.
     */
    public function emptyMessage(string $message): static
    {
        $this->emptyMessage = $message;
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

        // Convert Eloquent Collection to array
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            $data = [];
        }

        // Limit rows
        $data = array_slice($data, 0, $this->limit);

        // Transform rows if needed
        if ($this->rowTransformer) {
            $data = array_map($this->rowTransformer, $data);
        }

        // Build rows with only selected columns
        $rows = [];
        foreach ($data as $item) {
            $row = [];

            // If columns are specified, extract only those
            if (!empty($this->columns)) {
                foreach ($this->columns as $key => $config) {
                    $column = is_numeric($key) ? $config : $key;
                    $value = is_array($item) ? ($item[$column] ?? null) : ($item->$column ?? null);
                    $row[$column] = $value;
                }
            } else {
                // Use all data
                $row = is_array($item) ? $item : (array) $item;
            }

            // Add row URL if pattern is set
            if ($this->rowUrlPattern) {
                $row['_url'] = $this->buildRowUrl($item);
            }

            $rows[] = $row;
        }

        // Build column definitions
        $columnDefs = [];
        if (!empty($this->columns)) {
            foreach ($this->columns as $key => $config) {
                if (is_numeric($key)) {
                    $columnDefs[] = [
                        'key' => $config,
                        'label' => ucfirst(str_replace('_', ' ', $config)),
                    ];
                } else {
                    $columnDefs[] = array_merge(
                        ['key' => $key],
                        is_array($config) ? $config : ['label' => $config]
                    );
                }
            }
        } elseif (!empty($rows)) {
            // Auto-generate from first row
            foreach (array_keys($rows[0]) as $key) {
                if ($key !== '_url') {
                    $columnDefs[] = [
                        'key' => $key,
                        'label' => ucfirst(str_replace('_', ' ', $key)),
                    ];
                }
            }
        }

        return [
            'rows' => $rows,
            'columns' => $columnDefs,
            'total' => count($rows),
        ];
    }

    /**
     * Build a row URL from the pattern.
     */
    protected function buildRowUrl(mixed $item): string
    {
        $url = $this->rowUrlPattern;

        // Replace placeholders like {id}, {slug}, etc.
        preg_match_all('/\{(\w+)\}/', $url, $matches);

        foreach ($matches[1] as $key) {
            $value = is_array($item) ? ($item[$key] ?? '') : ($item->$key ?? '');
            $url = str_replace("{{$key}}", $value, $url);
        }

        return $url;
    }

    /**
     * Get additional data for this card type.
     */
    protected function additionalData(): array
    {
        return [
            'showHeaders' => $this->showHeaders,
            'emptyMessage' => $this->emptyMessage,
        ];
    }
}
