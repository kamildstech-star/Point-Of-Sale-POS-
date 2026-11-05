<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validate decimal strings before services run; never convert money to float. */
final class DecimalInput implements ValidationRule
{
    public function __construct(
        private bool $positive = false,
        private bool $signed = false,
        private bool $nonzero = false,
        private ?string $max = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = $this->signed ? '/^-?\d{1,12}(?:\.\d{1,4})?$/D' : '/^\d{1,12}(?:\.\d{1,4})?$/D';
        if (! is_string($value) || ! preg_match($pattern, $value)) {
            $fail('The :attribute must be a decimal string with at most 12 integer digits and four decimal places.');
        } elseif ($this->positive && bccomp($value, '0', 4) <= 0) {
            $fail('The :attribute must be greater than zero.');
        } elseif ($this->nonzero && bccomp($value, '0', 4) === 0) {
            $fail('The :attribute cannot be zero.');
        } elseif ($this->max !== null && bccomp($value, $this->max, 4) > 0) {
            $fail('The :attribute must be at most '.$this->max.'.');
        }
    }
}
