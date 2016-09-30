<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\ObjectManager;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Allow to find, delete, document in MongoDB
 *
 * @author poree
 */
class Repository {
    
    use \JPC\DesignPattern\Multiton;

    const MONGODB_QUERY_OPERATORS = ['$gt', '$lt', '$gte', '$lte', '$eq', '$ne', '$in', '$nin'];

    /**
     * Hydrator of model
     * @var Hydrator
     */
    private $hydrator;

    /**
     *
     * @var \MongoDB\Collection
     */
    private $collection;

    /**
     * Object Manager
     * @var ObjectManager
     */
    private $om;

    /**
     * Cache for object changes
     * @var ApcuCache 
     */
    private $objectCache;

    /**
     * Create new Repository
     * 
     * @param   Tools\ClassMetadata     $classMetadata      Metadata of managed class
     */
    public function __construct($classMetadata, $collection) {
        $this->modelName = $classMetadata->getName();

        $this->hydrator = Hydrator::getInstance($this->modelName, $classMetadata);

        $this->collection = DocumentManager::getInstance()->getMongoDBDatabase()->selectCollection($collection);

        $this->om = ObjectManager::getInstance();

        $this->objectCache = new ArrayCache();
    }

    /**
     * Get Hydrator used by the repository
     * 
     * @return Hydrator
     */
    public function getHydrator() {
        return $this->hydrator;
    }

    /**
     * Count corresponding documents for filters
     * 
     * @param   array                   $filters            Object
     * @param   array                   $options            Options for the query
     * @return  int                                         Number of corresponding documents
     */
    public function count($filters = [], $options = []) {
        return $this->collection->count($this->castMongoQuery($filters), $options);
    }

    /**
     * Find document by ID
     * 
     * @param   mixed                   $id                 Id of the document
     * @param   array                   $projections        Projection of the query
     * @param   array                   $options            Options for the query
     * @return  object                                      Object corresponding to MongoDB Document (false if not found)
     */
    public function find($id, $projections = [], $options = []) {
        $options = array_merge($options, [
            "projection" => $this->castMongoQuery($projections)
        ]);

        $result = $this->collection->findOne(["_id" => $id], $options);

        if ($result !== null) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);

            $this->cacheObject($object);
            $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }

        return false;
    }

    /**
     * Find all document of the collection
     * 
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findAll($projections = [], $sorts = [], $options = []) {
        $options = array_merge($options, [
            "projection" => $this->castMongoQuery($projections),
            "sort" => $this->castMongoQuery($sorts)
        ]);

        $result = $this->collection->find([], $options);

        $objects = [];

        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $this->cacheObject($object);
            $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
            $objects[] = $object;
        }

        return $objects;
    }

    /**
     * Find all document of the collection
     * 
     * @param   array                   $filters            Filters of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findBy($filters, $projections = [], $sorts = [], $options = []) {
        $options = array_merge($options, [
            "projection" => $this->castMongoQuery($projections),
            "sort" => $this->castMongoQuery($sorts)
        ]);

        $result = $this->collection->find($this->castMongoQuery($filters), $options);
        $objects = [];

        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $this->cacheObject($object);
            $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
            $objects[] = $object;
        }

        return $objects;
    }

    /**
     * Find all document of the collection
     * 
     * @param   array                   $filters            Filters of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findOneBy($filters = [], $projections = [], $sorts = [], $options = []) {

        $options = array_merge($options, [
            "projection" => $this->castMongoQuery($projections),
            "sort" => $this->castMongoQuery($sorts)
        ]);

        $result = $this->collection->findOne($this->castMongoQuery($filters), $options);

        if ($result != null) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);
            $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
            $this->cacheObject($object);
            return $object;
        }

        return null;
    }

    public function distinct($fieldName, $filters = [], $options = []) {
        $field = $this->hydrator->getFieldNameFor($fieldName);

        $result = $this->collection->distinct($field, $this->castMongoQuery($filters), $options);
        return $result;
    }

    public function drop() {
        $result = $this->collection->drop();

        if ($result->ok) {
            return true;
        } else {
            return false;
        }
    }

    private function castMongoQuery($query, $hydrator = null, $initial = true) {
        if (!isset($hydrator)) {
            $hydrator = $this->hydrator;
        }
        $new_query = [];
        foreach ($query as $name => $value) {
            $field = $hydrator->getFieldNameFor($name);
            $prop = $hydrator->getPropNameFor($field);
            if (is_array($value) && false != ($embName = $hydrator->isEmbedded($prop))) {
                $hydrator = $this->hydrator->getHydratorForField($field);
                $value = $this->castMongoQuery($value, $hydrator, false);
            }
            $new_query[$field] = $value;
        }

        if ($initial) {
            $new_query += $this->aggregArray($new_query);
            unset($new_query[$field]);
        }
        return $new_query;
    }

    private function cacheObject($object) {
        $this->objectCache->save(spl_object_hash($object), $this->hydrator->unhydrate($object));
    }

    private function uncacheObject($object) {
        return $this->objectCache->fetch(spl_object_hash($object));
    }

    public function getObjectChanges($object) {
        $new_datas = $this->hydrator->unhydrate($object);
        $old_datas = $this->uncacheObject($object);
        $changes = $this->compareDatas($new_datas, $old_datas);

        return $changes;
    }

    public function compareDatas($new, $old) {
        $changes = [];
        foreach ($new as $key => $value) {
            if (is_array($old) && array_key_exists($key, $old) && $old[$key] != null) {
                if (is_array($value) && is_array($old[$key])) {
                    $compare = true;
                    if (is_int(key($value))) {
                        $diff = array_diff_key($value, $old[$key]);
                        if (!empty($diff)) {
                            foreach ($diff as $diffKey => $diffValue) {
                                $changes[$key]['$push'][$diffKey] = $diffValue;
                            }
                            $compare = false;
                        }
                    }

                    if ($compare) {
                        $array_changes = $this->compareDatas($value, $old[$key]);
                        if (!empty($array_changes)) {
                            $changes[$key] = $array_changes;
                        }
                    }
                } else if ($value != $old[$key]) {
                    $changes[$key] = $value;
                }
            } else if (is_array($old) && array_key_exists($key, $old) && $old[$key] == null) {
                if ($old[$key] != $value) {
                    if (is_array($value) && is_int(key($value))) {
                        $changes[$key]['$push'] = $value;
                    } else if ($value == null && isset($old[$key])) {
                        $changes['$unset'][$key] = $value;
                    } else if (!isset($old[$key])) {
                        $changes[$key]['$set'] = $this->clearNullValues($value);
                    }
                }
            } else {
                if (is_array($value) && is_int(key($value)) && !isset($old)) {
                    $changes[$key]['$push'] = $value;
                } else if ($old != $value) {
                    if ($value == null) {
                        $changes['$unset'][$key] = $value;
                    } else if (!isset($old)) {
                        $changes[$key]['$set'] = $value;
                    }
                }
            }
        }

        return $changes;
    }

    public function getCollection() {
        return $this->collection;
    }

    private function aggregArray($datas, $prefix = '') {
        $realprefix = $prefix;
        if (!empty($prefix)) {
            $realprefix.='.';
        }
        $new = [];
        foreach ($datas as $key => $value) {
            if (is_array($value)) {
                $new = $this->aggregArray($value, $realprefix . $key);
            } else {
                if (in_array($key, self::MONGODB_QUERY_OPERATORS)) {
                    !isset($new[$prefix]) ? $new[$prefix] = [] : null;
                    $new[$prefix] += [$key => $value];
                } else {
                    $new[$realprefix . $key] = $value;
                }
            }
        }

        return $new;
    }

    private function clearNullValues(&$values) {
        foreach ($values as $key => &$value) {
            if (null === $value) {
                unset($values[$key]);
            } else if (is_array($value)) {
                $this->clearNullValues($value);
            }
        }

        return $values;
    }

}
