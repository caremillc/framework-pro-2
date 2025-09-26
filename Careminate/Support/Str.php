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
    function slugify(string $text, array $opts = []): string
    {
          /**
     * Create a URL-friendly slug from a string.
     *
     * @param string $text   Input text
     * @param array  $opts   Options:
     *                       - separator (string) default '-'
     *                       - limit (int|null) max length, default null
     *                       - lowercase (bool) default true
     *                       - transliterate (bool) default true
     *                       - ascii (bool) default true (if false keeps Unicode letters)
     *                       - locale (string|null) locale for lowercasing
     *
     * @return string
     */

        $options = array_merge([
            'separator'     => '-',
            'limit'         => null,
            'lowercase'     => true,
            'transliterate' => true,
            'ascii'         => true,
            'locale'        => null,
        ], $opts);

        $sep          = $options['separator'];
        $limit        = $options['limit'];
        $lowercase    = $options['lowercase'];
        $transliterate= $options['transliterate'];
        $asciiOnly    = $options['ascii'];
        $locale       = $options['locale'];

        // Normalize unicode (NFKD) if intl Normalizer available
        if (extension_loaded('intl') && class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_KD);
            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        // Transliteration (Latin → ASCII)
        if ($transliterate) {
            if (function_exists('transliterator_transliterate')) {
                $try = @transliterator_transliterate(
                    'Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove;',
                    $text
                );
                if ($try !== false && $try !== null) {
                    $text = $try;
                }
            } elseif (function_exists('iconv')) {
                $try = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
                if ($try !== false && $try !== null) {
                    $text = $try;
                }
            }
        }

        // Remove combining marks
        $text = preg_replace('/\p{M}/u', '', $text) ?? $text;

        if ($asciiOnly) {
            // Keep only ASCII letters, numbers, separators
            $text = preg_replace('/[^A-Za-z0-9\/_|+\- ]+/', '', $text) ?? $text;
            $text = preg_replace('/[\/_|+\- ]+/', $sep, $text) ?? $text;
        } else {
            // Keep any Unicode letters & numbers
            $text = preg_replace('/[^\p{L}\p{N}\/_|+\- ]+/u', '', $text) ?? $text;
            $text = preg_replace('/[\/_|+\- ]+/u', $sep, $text) ?? $text;
        }

        $text = trim($text, $sep);

        // Apply limit if set
        if ($limit !== null && $limit > 0) {
            if (function_exists('mb_substr')) {
                $text = mb_substr($text, 0, $limit);
            } else {
                $text = substr($text, 0, $limit);
            }
            $text = trim($text, $sep);
        }

        // Lowercase
        if ($lowercase) {
            if ($locale !== null && function_exists('mb_strtolower')) {
                $text = mb_strtolower($text, $locale);
            } else {
                $text = function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
            }
        }

        return $text === '' ? 'n-a' : $text;
    }
}


