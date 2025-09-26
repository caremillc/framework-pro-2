<?php declare(strict_types=1);

namespace Careminate\Support;

use ArrayAccess;

class Arr
{
    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function exists(array|ArrayAccess $array, string|int $key): bool
    {
        return $array instanceof ArrayAccess ? $array->offsetExists($key) : array_key_exists($key, $array);
    }

    public static function set(array &$array, string|int $key, mixed $value): void
    {
        $keys = explode('.', (string)$key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }
            $array = &$array[$segment];
        }
        $array[array_shift($keys)] = $value;
    }

    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) return $array;

        foreach (explode('.', (string)$key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public static function add(array $array, string|int $key, mixed $value): array
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }
        return $array;
    }

    public static function except(array $array, array|string $keys): array
    {
        foreach ((array)$keys as $key) {
            static::forget($array, $key);
        }
        return $array;
    }

    public static function has(array $array, string|int $key): bool
    {
        foreach (explode('.', (string)$key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }
        return true;
    }

    public static function forget(array &$array, string|int $key): void
    {
        $keys = explode('.', (string)$key);
        while (count($keys) > 1) {
            $segment = array_shift($keys);
            if (!isset($array[$segment]) || !is_array($array[$segment])) return;
            $array = &$array[$segment];
        }
        unset($array[array_shift($keys)]);
    }

    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) return $value;
        }

        return value($default);
    }

    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        return static::first(array_reverse($array, true), $callback, $default);
    }

    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];
        foreach ($array as $item) {
            if (!is_array($item)) $result[] = $item;
            elseif ($depth === 1) $result = array_merge($result, array_values($item));
            else $result = array_merge($result, static::flatten($item, $depth - 1));
        }
        return $result;
    }
}
