<?php declare(strict_types=1);


if (!function_exists('env')) {
    /**
     * Get an environment variable, or return the default value if not found.
     *
     * Supports various data types.
     *
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the environment variable is not found.
     * @return mixed The value of the environment variable or the default value.
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
        
        if (!is_string($value)) {
            return $value;
        }
        
        $trimmedValue = trim($value);
        
        return match (strtolower($trimmedValue)) {
            'true' => true,
            'false' => false,
            'null' => null,
            'empty' => '',
            default => is_numeric($trimmedValue) ? (str_contains($trimmedValue, '.') ? (float)$trimmedValue : (int)$trimmedValue) : (
                preg_match('/^[\[{].*[\]}]$/', $trimmedValue) ? (json_decode($trimmedValue, true) ?? $trimmedValue) : $trimmedValue
            )
        };
    }
}
