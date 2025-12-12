<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Blade;
use Throwable;

class ValidBladeTemplate implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        try {
            // Attempt to compile the Blade template
            Blade::compileString($value);
        } catch (Throwable $e) {
            $fail('The :attribute contains invalid Blade template syntax: '.$e->getMessage());
        }
    }
}
