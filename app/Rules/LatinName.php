<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LatinName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match("/^[A-Za-z][A-Za-z .'-]*$/", $value)) {
            $fail('The :attribute must use English letters only.');
        }
    }
}
