<?php

namespace JPC\MongoDB\ODM\Tools;

/**
 * Provide functions to modify array
 */
class ArrayModifier {

    const SPECIALS_KEYS = [];

    /**
     * Clear all null values of an array (and sub-arrays)
     * 
     * @param   array   $array          Array to clear
     * 
     * @return  array   Array cleaned
     */
    public static function clearNullValues(&$array) {
        foreach ($array as $key => &$value) {
            if (null === $value) {
                unset($array[$key]);
            } else if (is_array($value)) {
                self::clearNullValues($value);
            }
        }

        return $array;
    }

    public static function aggregate($array, $specialKeys = self::SPECIALS_KEYS, $prefix = '') {
        $new = [];
        foreach ($array as $key => $value) {
            $newKey = $key;
            if (!empty($prefix)) {
                $newKey = $prefix . '.' . $key;
            }
            if (!in_array($key, $specialKeys) && !in_array($key, array_keys($specialKeys))) {
                if (is_a($value, "stdClass")) {
                    $value = (array) $value;
                }

                if (is_array($value)) {
                    $new += self::aggregate($value, $specialKeys, $newKey);
                } else {
                    $new[$newKey] = $value;
                }
            } else {
                if (array_key_exists($key, $specialKeys) && is_callable($specialKeys[$key])) {
                    $new = call_user_func($specialKeys[$key], $prefix, $value, $new);
                }
            }
        }

        return $new;
    }

}
