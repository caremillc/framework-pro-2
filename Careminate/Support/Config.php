<?php declare(strict_types=1);

namespace Careminate\Support;

class Config
{
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        $path = BASE_PATH . '/config/' . $file . '.php';

        if (!file_exists($path)) {
            return $default;
        }

        $config = require $path;

        foreach ($parts as $part) {
            if (is_array($config) && array_key_exists($part, $config)) {
                $config = $config[$part];
            } else {
                return $default;
            }
        }

        return $config;
    }
}
