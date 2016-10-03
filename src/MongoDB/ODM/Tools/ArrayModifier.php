<?php

namespace JPC\MongoDB\ODM\Tools;

/**
 * Provide functions to modify array
 */
class ArrayModifier {

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

}
