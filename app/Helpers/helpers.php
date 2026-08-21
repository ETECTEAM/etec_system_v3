<?php


if(!function_exists('includeRouteFiles')) {
    function includeRouteFiles($folder): void
    {
        // function glob() scan folder and return all file in array
        foreach(glob($folder.'/*.php') as $routeFile) {
            require $routeFile;
        }
    }
}

if(!function_exists('setting')) {
    /**
     * Read a grading setting. All settings are cached in a single query and
     * the cache is cleared whenever a GradingSetting row is saved or deleted.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $rows = \Illuminate\Support\Facades\Cache::remember(
            \App\Models\GradingSetting::CACHE_KEY,
            3600,
            fn () => \App\Models\GradingSetting::query()
                ->get(['key', 'value', 'type'])
                ->keyBy('key'),
        );

        $row = $rows->get($key);

        if (! $row) {
            return $default;
        }

        return match ($row->type) {
            'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($row->value)
                ? (str_contains($row->value, '.') ? (float) $row->value : (int) $row->value)
                : $row->value,
            default => $row->value,
        };
    }
}