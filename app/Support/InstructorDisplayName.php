<?php

namespace App\Support;

class InstructorDisplayName
{
    public static function format(?string $name, string $fallback = '-'): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $fallback;
        }

        return trim(preg_split('/\s*[·•]\s*/u', $name, 2)[0] ?? $name) ?: $fallback;
    }
}
