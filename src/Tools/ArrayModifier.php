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
                if(empty($value)){
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }

    public static function aggregate($array, $specialKeys = self::SPECIALS_KEYS, $prefix = '') {
        $new = [];
        foreach ($array as $key => $value) {
            $newKey = (!empty($prefix)) ? $prefix . '.' . $key : $key;
            if (!in_array($key, $specialKeys, true) && !in_array($key, array_keys($specialKeys), true)) {
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
                    $new = call_user_func($specialKeys[$key], $prefix, $key, $value, $new);
                }
            }
        }

        return $new;
    }

    public static function disaggregate($array) {
        $new = [];
        foreach ($array as $key => $val) {
            if (false !== strpos($key, ".")) {
                list($realKey, $aggregated) = explode(".", $key, 2);
                $values = self::getDisaggregatedValues(preg_grep("/^$realKey/", array_keys($array)), $array);
                $new[$realKey] = self::disaggregate($values);
            } else {
                $new[$key] = $val;
            }
        }

        return $new;
    }

    private static function getDisaggregatedValues($keys, $array) {
        $values = [];
        foreach ($keys as $key) {
            $realKey = explode(".", $key, 2)[1];
            $values[$realKey] = $array[$key];
        }
        return $values;
    }

}
