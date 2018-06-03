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
     * @return  void
     */
    public function createUpdateQuery($old, $new, $prefix = "")
    {
        $update = [];

        foreach ($new as $key => $value) {
            if (is_array($old) && array_key_exists($key, $old)) {
                if (is_array($value) && strstr(key($value), '$') !== false) {
                    $update[key($value)][$prefix . $key] = $value[key($value)];
                } elseif (is_array($value) && is_array($old[$key])) {
                    if (!empty($old[$key])) {
                        $embeddedUpdate = array_merge_recursive($update, $this->createUpdateQuery($old[$key], $value, $prefix . $key . "."));

                        foreach ($embeddedUpdate as $updateOperator => $value) {
                            if (!isset($update[$updateOperator])) {
                                $update[$updateOperator] = [];
                            }
                            $update[$updateOperator] += $value;
                        }
                    } else {
                        $update['$set'][$prefix . $key] = $value;
                    }
                } else {
                    if ($value !== null && $value !== $old[$key]) {
                        $update['$set'][$prefix . $key] = $value;
                    } elseif ($value === null) {
                        $update['$unset'][$prefix . $key] = 1;
                    }
                }
            } else {
                if (is_array($value) && strstr(key($value), '$') === false) {
                    $embeddedQuery = $this->createUpdateQuery([], $value, $prefix . $key . ".");
                    if (count($embeddedQuery) == 1 && key($embeddedQuery) == '$set') {
                        $update['$set'][$prefix . $key] = $value;
                    } else {
                        $update = array_merge_recursive($update, $embeddedQuery);
                    }
                } elseif (is_array($value) && strstr(key($value), '$') !== false) {
                    $update[key($value)][$prefix . $key] = $value[key($value)];
                } else {
                    $update['$set'][$prefix . $key] = $value;
                }
            }

            unset($new[$key]);
            unset($old[$key]);
        }
        if (is_array($old)) {
            foreach (array_keys($old) as $key) {
                $update['$unset'][$prefix . $key] = 1;
            }
        }

        return $update;
    }
}
