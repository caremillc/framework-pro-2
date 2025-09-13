<?php declare(strict_types=1);

namespace Careminate\Support;

class Str
{
    /** Convert string to camelCase */
    public static function camel(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);
        return lcfirst($value);
    }

    /** Convert string to snake_case */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $value = preg_replace('/[A-Z]/', '_$0', $value);
        $value = strtolower($value);
        return ltrim($value, '_');
    }

    /** Convert string to kebab-case */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /** Convert string to Title Case */
    public static function title(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }

    /** Limit string length, append "..." if exceeded */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit) . $end;
    }

    /** Check if string contains a substring */
    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /** Check if string starts with a substring */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /** Check if string ends with a substring */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /** Replace sequentially in a string from an array */
    public static function replaceArray(string $search, array $replace, string $subject): string
    {
        foreach ($replace as $value) {
            $subject = preg_replace('/' . preg_quote($search, '/') . '/', $value, $subject, 1);
        }
        return $subject;
    }

    /** Return substring after a given value */
    public static function after(string $subject, string $search): string
    {
        if ($search === '') return $subject;
        $pos = strpos($subject, $search);
        return $pos === false ? '' : substr($subject, $pos + strlen($search));
    }

    /** Return substring before a given value */
    public static function before(string $subject, string $search): string
    {
        if ($search === '') return $subject;
        $pos = strpos($subject, $search);
        return $pos === false ? '' : substr($subject, 0, $pos);
    }

    /** Generate random string of given length */
    public static function random(int $length = 16): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }

    /** Convert string to lowercase */
    public static function lower(string $value): string
    {
        return mb_strtolower($value);
    }

    /** Convert string to uppercase */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value);
    }

    /** Convert string to slug (kebab-case, URL friendly) */
    public static function slug(string $title, string $separator = '-'): string
    {
        $title = preg_replace('/[^\pL\pN]+/u', $separator, $title);
        $title = trim($title, $separator);
        $title = mb_strtolower($title);
        return $title;
    }
}
