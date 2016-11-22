<?php

namespace JPC\MongoDB\ODM;

use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;
use JPC\MongoDB\ODM\Exception\ModelNotFoundException;
use JPC\MongoDB\ODM\ObjectManager;

/**
 * This allow to interact with document and repositories
 *
 * @author Joffrey Porée <contact@joffreyporee.com>
 */
class DocumentManager {
    /* ================================== */
    /*              CONSTANTS             */
    /* ================================== */

    /* =========== MODIFIERS ============ */

    const UPDATE_STATEMENT_MODIFIER = 0;
    const INSERT_STATEMENT_MODIFIER = 1;
    const HYDRATE_CONVERTION_MODIFIER = 2;
    const UNHYDRATE_CONVERTION_MODIFIER = 3;

    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

    /**
     * MongoDB Connection
     * @var MongoClient
     */
    private $mongoclient;

    /**
     * MongoDB Database
     * @var MongoDatabase
     */
    private $mongodatabase;

    /**
     * Annotation Reader
     * @var CacheReader
     */
    private $reader;
    private $repositories = [];

    /**
     * Object Manager
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Store coolection associated with object (for flush on special collection)
     * @var array 
     */
    private $objectCollection = [];

    /**
     * Class metadata factory
     * @var Tools\ClassMetadataFactory
     */
    private $classMetadataFactory;

    /**
     * Modifiers
     * @var array of callable 
     */
    private $modifiers = [];

    /* ================================== */
    /*          PUBLICS FUNCTIONS         */
    /* ================================== */

    /**
     * Create new Document manager
     * 
     * @param string        $mongouri   MongoDB URI
     * @param string        $db         MongoDB Database Name
     * @param boolean       $debug      Debug (Disable caching)
     */
    public function __construct($mongouri, $db, $debug = false) {
        if ($debug) {
            apcu_clear_cache();
        }

        $this->mongoclient = new MongoClient($mongouri);
        $this->mongodatabase = $this->mongoclient->selectDatabase($db);
        $this->classMetadataFactory = Tools\ClassMetadataFactory::getInstance();
        $this->objectManager = new ObjectManager();
    }

    /**
     * Allow to get MongoDB client
     * 
     * @return  MongoClient MongoDB Client
     */
    public function getMongoDBClient() {
        return $this->mongoclient;
    }

    /**
     * Allow to get MongoDB database
     * 
     * @return  MongoDatabase MongoDB database
     */
    public function getMongoDBDatabase() {
        return $this->mongodatabase;
    }

    /**
     * Allow to get repository for specified model
     * 
     * @param   string      $modelName  Name of the model
     * @param   string      $collection Name of the collection (null for get collection from document annotation)
     * 
     * @return  Repository  Repository for model
     *     
     * @throws  Exception\AnnotationException
     * @throws  ModelNotFoundException
     */
    public function getRepository($modelName, $collection = null) {
        $repIndex = $modelName . $collection;
        if (isset($this->repositories[$repIndex])) {
            return $this->repositories[$repIndex];
        }

        $classMetadata = $this->classMetadataFactory->getMetadataForClass($modelName);

        if (!isset($collection)) {
            $collection = $classMetadata->getCollection();
        }

        $repClass = $classMetadata->getRepositoryClass();

        $this->repositories[$repIndex] = new $repClass($this, $this->objectManager, $classMetadata, $collection);

        return $this->repositories[$repIndex];
    }

    /**
     * Add a modifier
     * 
     * @param   integer     $type       Type of modifier (See constants)
     * @param   callback    $callback   Functions will be called when modifiers call
     * @param   mixed       $id         ID to tag the modifier
     */
    public function addModifier($type, $callback, $id = null) {
        if (isset($id)) {
            $this->modifiers[$type][$id] = $callback;
        }
        $this->modifiers[$type][] = $callback;
    }

    /**
     * Allow to get modifiers for the specified type
     * 
     * @param   integer     $type       Type of modifier (See constants)
     * 
     * @return  mixed       All modifiers or false if there arn't modifiers for specified type
     */
    public function getModifier($type) {
        if (isset($this->modifiers[$type])) {
            return $this->modifiers[$type];
        }

        return [];
    }

    /**
     * Allow to get modifiers for the specified type
     * 
     * @param   integer     $type       Type of modifier (See constants)
     * 
     * @return  mixed       All modifiers or false if there arn't modifiers for specified type
     */
    public function removeModifier($type, $id) {
        if (array_key_exists($id, $this->modifiers[$type])) {
            unset($this->modifiers[$type][$id]);
            return true;
        }

        return false;
    }

    /**
     * Persist an object in object manager
     * 
     * @param   mixed       $object     Object to persist
     */
    public function persist($object, $collection = null) {
        if (isset($collection)) {
            $this->objectCollection[spl_object_hash($object)] = $collection;
        }
        $this->objectManager->addObject($object, ObjectManager::OBJ_NEW);
    }

    /**
     * Unpersist an object in object Manager
     * 
     * @param   mixed       $object     Object to unpersist
     */
    public function unpersist($object) {
        $this->objectManager->removeObject($object);
    }

    /**
     * Set object to be deleted at next flush
     * 
     * @param   mixed       $object     Object to delete
     */
    public function delete($object) {
        $this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
    }

    /**
     * Refresh an object to last MongoDB values
     * 
     * @param   mixed       $object     Object to refresh
     */
    public function refresh(&$object) {
        if(isset($this->objectCollection[spl_object_hash($object)])){
            $collection = $this->objectCollection[spl_object_hash($object)];
            $rep = $this->getRepository(get_class($object), $collection);
        } else {
            $rep = $this->getRepository(get_class($object));
        }
        
        $mongoCollection = $rep->getCollection();

        $datas = (array) $mongoCollection->findOne(["_id" => $rep->getHydrator()->unhydrate($object)["_id"]]);
        if ($rep instanceof GridFS\Repository) {
            $datas = $rep->createHytratableResult($datas);
        }

        if ($datas != null) {
            $rep->getHydrator()->hydrate($object, $datas);
            $rep->cacheObject($object);
        } else {
            $object = null;
        }
    }

    /**
     * Flush all changes and write it in mongoDB
     */
    public function flush() {
        $removeObjs = $this->objectManager->getObject(ObjectManager::OBJ_REMOVED);
        foreach ($removeObjs as $object) {
            $collection = isset($this->objectCollection[spl_object_hash($object)]) ? $this->objectCollection[spl_object_hash($object)] : $this->getRepository(get_class($object))->getCollection()->getCollectionName();
            $this->doRemove($collection, $object);
        }

        $updateObjs = $this->objectManager->getObject(ObjectManager::OBJ_MANAGED);
        foreach ($updateObjs as $object) {
            $collection = isset($this->objectCollection[spl_object_hash($object)]) ? $this->objectCollection[spl_object_hash($object)] : $this->getRepository(get_class($object))->getCollection()->getCollectionName();
            $this->update($collection, $object);
        }

        $newObjs = $this->objectManager->getObject(ObjectManager::OBJ_NEW);

        $toInsert = [];
        foreach ($newObjs as $object) {
            $collection = isset($this->objectCollection[spl_object_hash($object)]) ? $this->objectCollection[spl_object_hash($object)] : $this->getRepository(get_class($object))->getCollection()->getCollectionName();
            $toInsert[$collection][] = $object;
        }

        foreach ($toInsert as $collection => $objects) {
            $this->insert($collection, $objects);
        }
    }

    /**
     * Unmanaged (unpersist) all object
     */
    public function clear() {
        $this->objectManager->clear();
    }

    public function clearModifiers() {
        $this->modifiers = [];
    }

    /* ================================== */
    /*         PRIVATES FUNCTIONS         */
    /* ================================== */

    /**
     * Insert object into mongoDB
     * 
     * @param   mixed       $object     Object to insert
     */
    private function insert($collection, $objects) {
        if ($pos = strpos($collection, ".files")) {
            $collection = substr($collection, 0, $pos);
        }
        $rep = $this->getRepository(get_class($objects[key($objects)]), $collection);
        $hydrator = $rep->getHydrator();

        if (is_a($rep, "JPC\MongoDB\ODM\GridFS\Repository")) {
            $bucket = $rep->getBucket();

            foreach ($objects as $obj) {
                $datas = $hydrator->unhydrate($obj);
                $stream = $datas["stream"];

                $options = $datas;
                unset($options["stream"]);
                if (isset($datas["_id"]) && $datas["_id"] === null) {
                    unset($options["_id"]);
                }

                $filename = isset($options["filename"]) && null != $datas["filename"] ? $datas["filename"] : md5(uniqid());

                if (isset($options["filename"])) {
                    unset($options["filename"]);
                }

                foreach ($options["metadata"] as $key => $value) {
                    if (null === $value) {
                        unset($options["metadata"][$key]);
                    }
                }

                if (empty($options["metadata"])) {
                    unset($options["metadata"]);
                }


                $id = $bucket->uploadFromStream($filename, $stream, $options);

                $hydrator->hydrate($obj, ["_id" => $id]);
                $this->objectManager->setObjectState($obj, ObjectManager::OBJ_MANAGED);
                $this->refresh($obj);
            }
        } else {
            $collection = $rep->getCollection();

            $datas = [];
            foreach ($objects as $object) {
                $datas[] = $hydrator->unhydrate($object);
                Tools\ArrayModifier::clearNullValues($datas);
            }

            foreach ($this->getModifier(self::INSERT_STATEMENT_MODIFIER) as $callback) {
                $datas = call_user_func($callback, $update, $object);
            }

            $res = $collection->insertMany($datas);


            if ($res->isAcknowledged()) {
                foreach ($res->getInsertedIds() as $index => $id) {
                    $hydrator->hydrate($objects[$index], ["_id" => $id]);
                    $this->objectManager->setObjectState($objects[$index], ObjectManager::OBJ_MANAGED);
                    $this->refresh($objects[$index]);
                    $rep->cacheObject($objects[$index]);
                }
            }
        }
    }

    /**
     * Update object into mongoDB
     * 
     * @param   mixed       $object     Object to update
     */
    private function update($collection, $object) {
        $rep = $this->getRepository(get_class($object), $collection);
        $collection = $rep->getCollection();

        $diffs = $rep->getObjectChanges($object);
        $update = $this->createUpdateQueryStatement($diffs);

        foreach ($this->getModifier(self::UPDATE_STATEMENT_MODIFIER) as $callback) {
            $update = call_user_func($callback, $update, $object);
        }
        
        $hydrator = $rep->getHydrator();
        
        $id = $hydrator->unhydrate($object)["_id"];

        if (!empty($update)) {
            $res = $collection->updateOne(["_id" => $id], $update);
            if ($res->isAcknowledged()) {
                $this->refresh($object);
                $rep->cacheObject($object);
            }
        }
    }

    /**
     * Remove object from MongoDB
     * 
     * @param   mixed       $object     Object to insert
     */
    private function doRemove($collection, $object) {
        $rep = $this->getRepository(get_class($object), $collection);
        $collection = $rep->getCollection();
        
        $id = $rep->getHydrator()->unhydrate($object)["_id"];

        $res = $collection->deleteOne(["_id" => $id]);

        if ($res->isAcknowledged()) {
            $this->objectManager->removeObject($object);
        }
    }

    /* ================================== */
    /*       THIS FUNCTIONS WIIL BE       */
    /*        MODIFIED AND UPDATED        */
    /* ================================== */

    private function createUpdateQueryStatement($datas) {
        $update = [];

        $update['$set'] = [];
        foreach ($datas as $key => $value) {
            $push = null;
            if ($key == '$set') {
                $update['$set'] += $value;
            } else if (is_array($value)) {
                $push = $this->checkPush($value, $key);
                if ($push != null) {
                    foreach ($push as $field => $fieldValue) {
                        $update['$push'][$field] = ['$each' => $fieldValue];
                    }
                }
                $update['$set'] += Tools\ArrayModifier::aggregate($value, [
                            '$set' => [$this, 'onAggregSet']
                                ], $key);
            } else {
                $update['$set'][$key] = $value;
            }
        }

        foreach ($update['$set'] as $key => $value) {
            if (strstr($key, '$push')) {
                unset($update['$set'][$key]);
            }

            if (array_key_exists($key, $update['$set']) && $update['$set'][$key] === null) {
                unset($update['$set'][$key]);
                $update['$unset'][$key] = "";
            }
        }

        if (empty($update['$set'])) {
            unset($update['$set']);
        }

        return $update;
    }

    private function checkPush($array, $prefix = '') {
        foreach ($array as $key => $value) {
            if ($key === '$push') {
                $return = [];
                foreach ($value as $toPush) {
                    $return[] = $toPush;
                }
                return [$prefix => $return];
            } else if (is_array($value)) {
                if (null != ($push = $this->checkPush($value, $prefix . '.' . $key))) {
                    return $push;
                }
            }
        }
    }

    public function onAggregSet($prefix, $key, $value, $new) {
        if (is_array($value)) {
            foreach ($value as $k => $val) {
                $new[$prefix][$k] = $val;
            }
        } else {
            $new[$prefix] = $value;
        }

        return $new;
    }

}
