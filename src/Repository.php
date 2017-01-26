<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Tools\ClassMetadata;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Allow to find, delete, document in MongoDB
 *
 * @author poree
 */
class Repository {

    /**
     * Document manager
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * Class metadatas
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * Hydrator of model
     * @var Hydrator
     */
    protected $hydrator;

    /**
     * MongoDB collection
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
    public function __construct(DocumentManager $documentManager, ObjectManager $objectManager, ClassMetadata $classMetadata, $collection) {
        $this->documentManager = $documentManager;
        $this->classMetadata = $classMetadata;
        $this->modelName = $classMetadata->getName();
        $this->hydrator = Hydrator::getInstance($this->modelName . spl_object_hash($documentManager), $documentManager, $classMetadata);

        if(is_string($collection)){
            $this->createCollection($collection, $classMetadata);
            $this->collection = $this->documentManager->getMongoDBDatabase()->selectCollection($collection, $classMetadata->getCollectionOptions());
        } else {
            $this->collection = $collection;
        }

        $this->objectManager = $objectManager;
        $this->objectCache = new ArrayCache();
    }
    
    /**
     * Create the collection
     * 
     * @param   string                  $collectionName     Name of the collection
     * @param   ClassMetadata           $classMetadata      Metadatas of the model
     */
    private function createcollection($collectionName, ClassMetadata $classMetadata) {
        $db = $this->documentManager->getMongoDBDatabase();
        foreach ($db->listCollections()as $collection) {
            if ($collection->getName() == $collectionName) {
                return;
            }
        }

        $options = $classMetadata->getCollectionCreationOptions();

        if (!empty($options)) {
            $db->createCollection($collectionName, $options);
            $this->documentManager->getLogger()->debug("Create collection '$collectionName', see metadata for options", ["options" => $options]);
        }
    }

    /**
     * Get the collection
     * 
     * @return \MongoDB\Collection A mongoDb collection
     */
    public function getCollection() {
        return $this->collection;
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
        return $this->collection->count($this->castQuery($filters), $options);
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
            "projection" => $this->castQuery($projections)
            ]);

        $this->documentManager->getLogger()->debug("Find object in collection '".$this->collection->getCollectionName()."' with id : '".(string) $id."'");
        $result = $this->collection->findOne(["_id" => $id], $options);

        if ($result !== null) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);
            $this->cacheObject($object);
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }

        return null;
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
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
            ]);

        $this->documentManager->getLogger()->debug("Find all document in collection '".$this->collection->getCollectionName()."'");

        $result = $this->collection->find([], $options);

        $objects = [];

        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $this->cacheObject($object);
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
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
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
            ]);

        $filters = $this->castQuery($filters);

        $this->documentManager->getLogger()->debug("Find documents in collection '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "options" => $options
            ]);

        $result = $this->collection->find($filters, $options);
        $objects = [];

        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $this->cacheObject($object);
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
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
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
            ]);

        $filters = $this->castQuery($filters);

        $this->documentManager->getLogger()->debug("Find one document in collection '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "options" => $options
            ]);

        $result = $this->collection->findOne($filters, $options);

        if ($result != null) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
            $this->cacheObject($object);
            return $object;
        }

        return null;
    }

    /**
     * FindAndModifyOneBy document
     *
     * @param   array                   $filters            Filters of the query
     * @param   array                   $update             Update of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  object                                      Object correspoding to finding element 
     */
    public function findAndModifyOneBy($filters = [], $update = [], $projections = [], $sorts = [], $options = []) {

        $options = array_merge($options, [
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
            ]);

        $filters = $this->castQuery($filters);
        $update = $this->castQuery($update);

        $this->documentManager->getLogger()->debug("Find and update one document in collection '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "update" => $update,
            "options" => $options
            ]);

        $result = (array) $this->collection->findOneAndUpdate($filters, $update, $options);

        if ($result != null) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
            $this->cacheObject($object);
            return $object;
        }

        return null;
    }

    public function getTailableCursor($filters = [], $options = []) {
        $options['cursorType'] = \MongoDB\Operation\Find::TAILABLE_AWAIT;

        return $this->collection->find($this->castQuery($filters), $options);
    }

    public function distinct($fieldName, $filters = [], $options = []) {
        $propInfos = $this->classMetadata->getPropertyInfoForField($fieldName);
        if(!$propInfos){
            $propInfos = $this->classMetadata->getPropertyInfo($fieldName);
        }
        
        $field = $propInfos->getField();

        $filters = $this->castQuery($filters);

        $this->documentManager->getLogger()->debug("Get distinct value of field '$field' in '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "options" => $options
            ]);

        $result = $this->collection->distinct($field, $filters, $options);
        return $result;
    }

    public function drop() {
        $this->documentManager->getLogger()->debug("Drop collection '".$this->collection->getCollectionName()."'");
        $result = $this->collection->drop();

        if ($result->ok) {
            return true;
        } else {
            return false;
        }
    }

    protected function castQuery($query) {
        $qc = new Tools\QueryCaster($query, $this->classMetadata);
        return $qc->getCastedQuery();
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

}
