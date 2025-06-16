<?php

namespace App\Core\Helpers;

class ArrayExtractor
{
    public static function extract(array $source, array $fields): array
    {
        $result = [];

        foreach ($fields as $field => $path) {
            $result[$field] = self::getValue($source, $path);
        }

        return $result;
    }

    private static function getValue(?array $array, $path)
    {
        if (is_null($array)) {
            return null;
        }

        $keys = is_array($path) ? $path : explode('.', $path);
        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return null;
            }
        }
        return $array;
    }
}