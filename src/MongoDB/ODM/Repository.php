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

    protected static $mongoDbQueryOperators;
    
    /**
     * Document manager
     * @var DocumentManager 
     */
    protected $documentManager;

    /**
     * Hydrator of model
     * @var Hydrator
     */
    protected $hydrator;

    /**
     *
     * @var \MongoDB\Collection
     */
    protected $collection;

    /**
     * Object Manager
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Cache for object changes
     * @var ApcuCache 
     */
    protected $objectCache;

    /**
     * Create new Repository
     * 
     * @param   Tools\ClassMetadata     $classMetadata      Metadata of managed class
     */
    public function __construct(DocumentManager $documentManager, ObjectManager $objectManager, $classMetadata, $collection) {
        if (!isset(self::$mongoDbQueryOperators)) {
            $callBack = [$this, 'aggregOnMongoDbOperators'];
            self::$mongoDbQueryOperators = [
                '$gt' => $callBack, '$lt' => $callBack, '$gte' => $callBack, '$lte' => $callBack, '$eq' => $callBack, '$ne' => $callBack, '$in' => $callBack, '$nin' => $callBack
            ];
        }

        $this->documentManager = $documentManager;
        $this->modelName = $classMetadata->getName();
        $this->hydrator = Hydrator::getInstance($this->modelName . spl_object_hash($documentManager), $documentManager, $classMetadata);
        $this->createCollection($collection, $classMetadata);
        $this->collection = $this->documentManager->getMongoDBDatabase()->selectCollection($collection);
        
        $this->objectManager = $objectManager;
        $this->objectCache = new ArrayCache();
    }
    
    private function createcollection($collectionName, $classMetadata){
        $db = $this->documentManager->getMongoDBDatabase();
        foreach ($db->listCollections()as $collection){
            if($collection->getName() == $collectionName){
                return;
            }
        }

        $options = [];
        
        /**
         * INIT OPTIONS HERE
         */
        
        $db->createCollection($collectionName, $options);
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
            $this->documentManager->persist($object, $this->collection->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
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
            $this->documentManager->persist($object, $this->collection->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
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
            $this->documentManager->persist($object, $this->collection->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
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
            $this->cacheObject($object);
            $this->documentManager->persist($object, $this->collection->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }

        return null;
    }

    public function getTailableCursor($filters = [], $options = []) {
        $options['cursorType'] = \MongoDB\Operation\Find::TAILABLE_AWAIT;

        return $this->collection->find($this->castMongoQuery($filters), $options);
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

    public function castMongoQuery($query, $hydrator = null, $initial = true) {
        if (!isset($hydrator)) {
            $hydrator = $this->hydrator;
        }
        $new_query = [];
        foreach ($query as $name => $value) {
            $field = $hydrator->getFieldNameFor($name);
            $realfield = explode(".", $field, 2)[0];
            $prop = $hydrator->getPropNameFor($realfield);
            if (is_array($value) && false != ($embName = $hydrator->isEmbedded($prop))) {
                $hydrator = $this->hydrator->getHydratorForField($field);
                $value = $this->castMongoQuery($value, $hydrator, false);
            }
            $new_query[$field] = $value;

            if ($initial) {
                $new_query = Tools\ArrayModifier::aggregate($new_query, self::$mongoDbQueryOperators);
            }
        }

        return $new_query;
    }

    public function cacheObject($object) {
        if (is_object($object)) {
            $this->objectCache->save(spl_object_hash($object), $this->hydrator->unhydrate($object));
        }
    }

    protected function uncacheObject($object) {
        return $this->objectCache->fetch(spl_object_hash($object));
    }

    public function getObjectChanges($object) {
        $new_datas = $this->hydrator->unhydrate($object);
        $old_datas = $this->uncacheObject($object);
        $changes = $this->compareDatas($new_datas, $old_datas);

        return $changes;
    }

    protected function compareDatas($new, $old) {
        $changes = [];
        foreach ($new as $key => $value) {
            if (is_array($old) && array_key_exists($key, $old) && $old[$key] !== null) {
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

                        $diff = array_diff_key($old[$key], $value);
                        if ($compare && !empty($diff)) {
                            foreach ($diff as $diffKey => $diffValue) {
                                $value[$diffKey] = null;
                            }
                        }
                    }

                    if ($compare) {
                        $array_changes = $this->compareDatas($value, $old[$key]);
                        if (!empty($array_changes)) {
                            $changes[$key] = $array_changes;
                        }
                    }
                } else if ($value != $old[$key] || $value !== $old[$key]) {
                    $changes[$key]['$set'] = $value;
                }
            } else if (is_array($old) && array_key_exists($key, $old) && $old[$key] === null) {
                if ($old[$key] != $value) {
                    if (is_array($value) && is_int(key($value))) {
                        $changes[$key]['$push'] = $value;
                    } else if ($value === null && isset($old[$key])) {
                        $changes['$unset'][$key] = $value;
                    } else if (!isset($old[$key]) && is_array($value)) {
                        $changes[$key]['$set'] = Tools\ArrayModifier::clearNullValues($value);
                    } else if (!isset($old[$key])) {
                        $changes[$key]['$set'] = $value;
                    }
                }
            } else {
                if (is_array($value) && is_int(key($value)) && !isset($old)) {
                    $changes[$key]['$push'] = $value;
                } else if ($old != $value) {
                    if ($value === null) {
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

    public function aggregOnMongoDbOperators($prefix, $key, $value, $new) {
        !isset($new[$prefix]) ? $new[$prefix] = [] : null;
        $new[$prefix] += [$key => $value];

        return $new;
    }

}
