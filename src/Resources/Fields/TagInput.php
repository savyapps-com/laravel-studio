<?php

namespace SavyApps\LaravelStudio\Resources\Fields;

class TagInput extends Field
{
    protected array $suggestions = [];

    protected bool $allowCustom = true;

    protected ?int $maxTags = null;

    protected function fieldType(): string
    {
        return 'tag-input';
    }

    /**
     * Set suggested tags that appear in the dropdown.
     */
    public function suggestions(array $suggestions): static
    {
        $this->suggestions = $suggestions;

        return $this->meta(['suggestions' => $suggestions]);
    }

    /**
     * Whether to allow custom tags beyond the suggestions.
     */
    public function allowCustom(bool $allow = true): static
    {
        $this->allowCustom = $allow;

        return $this->meta(['allowCustom' => $allow]);
    }

    /**
     * Set maximum number of tags allowed.
     */
    public function maxTags(?int $max): static
    {
        $this->maxTags = $max;

        return $this->meta(['maxTags' => $max]);
    }

    /**
     * Set minimum number of tags required.
     */
    public function minTags(?int $min): static
    {
        return $this->meta(['minTags' => $min]);
    }

    /**
     * Make the input case insensitive when checking for duplicates.
     */
    public function caseInsensitive(bool $caseInsensitive = true): static
    {
        return $this->meta(['caseInsensitive' => $caseInsensitive]);
    }

    /**
     * Set a custom delimiter for separating tags (default is Enter key).
     */
    public function delimiter(string $delimiter): static
    {
        return $this->meta(['delimiter' => $delimiter]);
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
