<?php

namespace JPC\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class QueryCaster
{

    /**
     * Contain all of MongoDD Operators
     * @var array<string>
     */
    protected static $mongoDbQueryOperators;

    /**
     * Initial Query
     * @var array
     */
    private $query;

    /**
     * Inital class metadata
     * @var ClassMetadata
     */
    private $initialMetadata;

    /**
     * last used class metadata by query caster
     * @var ClassMetadata
     */
    private $lastUsedMetadata;

    /**
     * Class metadata factory
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;

    /**
     * Create query caster
     *
     * @param   ClassMetadata           $classMetadata
     * @param   ClassMetadataFactory    $classMetadataFactory
     */
    public function __construct(ClassMetadata $classMetadata, ClassMetadataFactory $classMetadataFactory)
    {
        $this->initialMetadata = $classMetadata;
        if (!isset(self::$mongoDbQueryOperators)) {
            $callBack = [$this, 'aggregOnMongoDbOperators'];
            self::$mongoDbQueryOperators = [
                '$elemMatch' => $callBack, '$gt' => $callBack, '$lt' => $callBack, '$gte' => $callBack, '$lte' => $callBack, '$eq' => $callBack, '$ne' => $callBack, '$in' => $callBack, '$nin' => $callBack,
            ];
        }
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * Set the query to cast
     *
     * @param   array   $query  Query to cast
     * @return  void
     */
    public function init($query)
    {
        $this->query = $query;
    }

    /**
     * Get the caster query
     *
     * @return array
     */
    public function getCastedQuery()
    {
        $newQuery = $this->castArray($this->query, $this->initialMetadata);
        ArrayModifier::aggregate($newQuery, self::$mongoDbQueryOperators);
        return $newQuery;
    }

    /**
     * Get uncasted query
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Undocumented function
     *
     * @param [type] $array
     * @param [type] $classMetadata
     * @return void
     */
    private function castArray($array, $classMetadata)
    {
        $newQuery = [];
        if (is_array($array)) {
            foreach ($array as $field => $value) {
                $field = $this->castDottedString($field, $classMetadata);
                if (is_array($value)) {
                    if (false != ($fieldInfos = $classMetadata->getPropertyInfoForField($field)) && $fieldInfos->getEmbedded()) {
                        $value = $this->castArray($value, $this->lastUsedMetadata);
                    }

                    if (false != ($fieldInfos = $classMetadata->getPropertyInfoForField($field)) && $fieldInfos->getMultiEmbedded()) {
                        $newValue = [];
                        foreach ($value as $v) {
                            $newValue[] = $this->castArray($v, $this->lastUsedMetadata);
                        }
                        $value = $newValue;
                    }
                }

                $newQuery[$field] = $value;
            }
        }

        return $newQuery;
    }

    private function castDottedString($string, $classMetadata = null)
    {
        if (!isset($classMetadata)) {
            $classMetadata = $this->initialMetadata;
        }
        $this->lastUsedMetadata = $classMetadata;

        $exploded = explode(".", $string, 2);
        $realField = $exploded[0];
        if (isset($exploded[1])) {
            $remainingField = $exploded[1];
        }

        $propInfo = ($classMetadata->getPropertyForField($realField)) ? $classMetadata->getPropertyInfoForField($realField) : $classMetadata->getPropertyInfo($realField);

        if ($propInfo != false && ($propInfo->getEmbedded() || $propInfo->getMultiEmbedded())) {
            $this->lastUsedMetadata = $classMetadata = $this->classMetadataFactory->getMetadataForClass($propInfo->getEmbeddedClass());
            if (isset($remainingField)) {
                return $propInfo->getField() . "." . $this->castDottedString($remainingField, $classMetadata);
            }
            $result = $propInfo->getField();
            if ($propInfo->getMetadata()) {
                $result = "metadata." . $result;
            }
            return $result;
        } elseif ($propInfo != false) {
            if (!empty($remainingField)) {
                $remainingField = "." . $remainingField;
            } else {
                $remainingField = "";
            }

            $result = $propInfo->getField() . $remainingField;
            if ($propInfo->getMetadata()) {
                $result = "metadata." . $result;
            }
            return $result;
        } else {
            return $string;
        }
    }

    private function aggregOnMongoDbOperators($prefix, $key, $value, $new)
    {
        !isset($new[$prefix]) ? $new[$prefix] = [] : null;
        $new[$prefix] += [$key => $value];

        return $new;
    }
}
