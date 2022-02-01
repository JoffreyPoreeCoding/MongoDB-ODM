<?php

namespace JPC\MongoDB\ODM\Tools;

/**
 * Create MongoDB Update queries
 */
class UpdateQueryCreator
{

    /**
     * Create an update query from old and new values of a document
     *
     * @param   array   $old        Old values of document
     * @param   array   $new        New values of document
     * @param   string  $prefix
     * @return  array
     */
    public function createUpdateQuery($old, $new, $prefix = "")
    {
        $update = [];

        foreach ($new as $key => $value) {
            if (is_array($old) && array_key_exists($key, $old)) {
                if (is_array($value) && !empty($value) && strstr(key($value), '$') !== false) {
                    $update[key($value)][$prefix . $key] = $value[key($value)];
                } elseif (is_array($value) && !empty($value) && is_array($old[$key])) {
                    if (!empty($old[$key])) {
                        $embeddedUpdate = array_merge_recursive($update, $this->createUpdateQuery($old[$key], $value, $prefix . $key . "."));
                        if (isset($embeddedUpdate['$unset']) && array_values($old[$key]) === $old[$key] && $value !== null && count(array_filter(array_keys($value), 'is_string')) == 0) {
                            $update['$set'][$prefix . $key] = array_values($value);
                        } else {
                            foreach ($embeddedUpdate as $updateOperator => $value) {
                                if (!isset($update[$updateOperator])) {
                                    $update[$updateOperator] = [];
                                }
                                $update[$updateOperator] += $value;
                            }
                        }
                    } elseif (!empty($value)) {
                        $update['$set'][$prefix . $key] = $value;
                    }
                } else {
                    if ($value !== null && $value !== $old[$key]) {
                        if ($value instanceof \MongoDB\BSON\UTCDateTime && $old[$key] instanceof \MongoDB\BSON\UTCDateTime) {
                            if (serialize($value) != serialize($old[$key])) {
                                $update['$set'][$prefix . $key] = $value;
                            }
                        } else {
                            $update['$set'][$prefix . $key] = $value;
                        }
                    } elseif ($value === null && $old[$key] !== null) {
                        $update['$unset'][$prefix . $key] = 1;
                    }
                }
            } else {
                if (is_array($value) && !empty($value) && strstr(key($value), '$') === false) {
                    $embeddedQuery = $this->createUpdateQuery([], $value, $prefix . $key . ".");
                    if (count($embeddedQuery) == 1 && key($embeddedQuery) == '$set') {
                        $update['$set'][$prefix . $key] = $value;
                    } else {
                        $update = array_merge_recursive($update, $embeddedQuery);
                    }
                } elseif (is_array($value) && !empty($value) && strstr(key($value), '$') !== false) {
                    $update[key($value)][$prefix . $key] = $value[key($value)];
                } else {
                    $update['$set'][$prefix . $key] = $value;
                }
            }

            unset($new[$key]);
            if (is_array($old)) {
                unset($old[$key]);
            }
        }
        if (is_array($old)) {
            foreach (array_keys($old) as $key) {
                $update['$unset'][$prefix . $key] = 1;
            }
        }

        if (isset($update['$set']['_id'])) {
            unset($update['$set']['_id']);
        }

        foreach ($update as $modifier => $value) {
            if (empty($value)) {
                unset($update[$modifier]);
            }
        }

        return $update;
    }
}
