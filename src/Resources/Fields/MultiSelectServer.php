<?php

namespace SavyApps\LaravelStudio\Resources\Fields;

class MultiSelectServer extends Field
{
    protected string $endpointUrl = '';

    protected string $labelKey = 'label';

    protected string $valueKey = 'value';

    protected function fieldType(): string
    {
        return 'multi-select-server';
    }

    /**
     * Set the API endpoint URL to fetch options from.
     */
    public function endpoint(string $url): static
    {
        $this->endpointUrl = $url;

        return $this->meta(['endpoint' => $url]);
    }

    /**
     * Set the key used for option labels.
     */
    public function labelKey(string $key): static
    {
        $this->labelKey = $key;

        return $this->meta(['labelKey' => $key]);
    }

    /**
     * Set the key used for option values.
     */
    public function valueKey(string $key): static
    {
        $this->valueKey = $key;

        return $this->meta(['valueKey' => $key]);
    }

    /**
     * Set whether the select should be searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        return $this->meta(['searchable' => $searchable]);
    }

    /**
     * Set the maximum number of selections allowed.
     */
    public function maxSelections(?int $max): static
    {
        return $this->meta(['maxSelections' => $max]);
    }

    /**
     * Set a minimum number of selections required.
     */
    public function minSelections(?int $min): static
    {
        return $this->meta(['minSelections' => $min]);
    }

    /**
     * Set the group key for grouping options.
     */
    public function groupBy(string $key): static
    {
        return $this->meta(['groupBy' => $key]);
    }

    /**
     * Set description key for showing option descriptions.
     */
    public function descriptionKey(string $key): static
    {
        return $this->meta(['descriptionKey' => $key]);
    }

    /**
     * Set whether to show selected items as tags.
     */
    public function showTags(bool $show = true): static
    {
        return $this->meta(['showTags' => $show]);
    }

    /**
     * Set custom request headers for the API call.
     */
    public function headers(array $headers): static
    {
        return $this->meta(['headers' => $headers]);
    }

    /**
     * Transform the value for display.
     */
    public function transformValue(mixed $value, $model): mixed
    {
        // Ensure we always return an array
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            // Try to decode JSON string
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [$value];
        }

        return is_array($value) ? $value : [$value];
    }
}
