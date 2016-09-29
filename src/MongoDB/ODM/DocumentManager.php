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

    use \JPC\DesignPattern\Singleton;

    /* ================================== */
    /*              CONSTANTS             */
    /* ================================== */

    /* =========== MODIFIERS ============ */

    const UPDATE_STATEMENT_MODIFIER = 0;
    const HYDRATE_CONVERTION_MODIFIER = 1;
    const UNHYDRATE_CONVERTION_MODIFIER = 2;

    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

    /**
     * Contain all models paths
     * @var array
     */
    private $modelPaths = [];

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

    /**
     * Object Manager
     * @var ObjectManager
     */
    private $om;

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
        $this->om = ObjectManager::getInstance();
    }

    /**
     * Add model path (Diroctory that contain models)
     * 
     * @param string        $identifier Name for the path
     * @param string        $path       Path to folder
     */
    public function addModelPath($identifier, $path) {
        $this->modelPaths[$identifier] = $path;
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
        $rep = "JPC\MongoDB\ODM\Repository";
        foreach ($this->modelPaths as $modelPath) {
            if (file_exists($modelPath . "/" . str_replace("\\", "/", $modelName) . ".php")) {
                require_once $modelPath . "/" . str_replace("\\", "/", $modelName) . ".php";
                $classMeta = $this->classMetadataFactory->getMetadataForClass($modelName);
                if (!$classMeta->hasClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document")) {
                    throw new Exception\AnnotationException("Model '$modelName' need to have 'Document' annotation.");
                } else {
                    $docAnnotation = $classMeta->getClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document");
                    $rep = isset($docAnnotation->repositoryClass) ? $docAnnotation->repositoryClass : $rep;
                    $collection = isset($collection) ? $collection : $docAnnotation->collectionName;
                }
                break;
            }
        }

        if (!isset($classMeta)) {
            throw new ModelNotFoundException($modelName);
        }

        return $rep::getInstance($modelName, $classMeta, $collection);
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

        return false;
    }

    /**
     * Persist an object in object manager
     * 
     * @param   mixed       $object     Object to persist
     */
    public function persist($object) {
        $this->om->addObject($object);
    }

    /**
     * Unpersist an object in object Manager
     * 
     * @param   mixed       $object     Object to unpersist
     */
    public function unpersist($object) {
        $this->om->removeObject($object);
    }

    /**
     * Set object to be deleted at next flush
     * 
     * @param   mixed       $object     Object to delete
     */
    public function delete($object) {
        $this->om->setObjectState($object, ObjectManager::OBJ_REMOVED);
    }

    /**
     * Refresh an object to last MongoDB values
     * 
     * @param   mixed       $object     Object to refresh
     */
    public function refresh(&$object) {
        $rep = $this->getRepository(get_class($object));
        $collection = $rep->getCollection();
        $datas = $collection->findOne(["_id" => $object->getId()]);
        if ($datas != null) {
            $rep->getHydrator()->hydrate($object, $datas);
        } else {
            $object = null;
        }
    }

    /**
     * Flush all changes and write it in mongoDB
     */
    public function flush() {
        $removeObjs = $this->om->getObject(ObjectManager::OBJ_REMOVED);
        foreach ($removeObjs as $object) {
            $this->doRemove($object);
        }

        $updateObjs = $this->om->getObject(ObjectManager::OBJ_MANAGED);
        foreach ($updateObjs as $object) {
            $this->update($object);
        }

        $newObjs = $this->om->getObject(ObjectManager::OBJ_NEW);

        $toInsert = [];
        foreach ($newObjs as $object) {
            $toInsert[$this->getRepository(get_class($object))->getCollection()->getCollectionName()][] = $object;
        }

        foreach ($toInsert as $collection => $objects) {
            $this->insert($collection, $objects);
        }
    }

    /**
     * Unmanaged (unpersist) all object
     */
    public function clear() {
        $this->om->clear();
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
        $collection = $this->mongodatabase->selectCollection($collection);

        $hydrator = $this->getRepository(get_class($objects[key($objects)]))->getHydrator();

        $datas = [];
        foreach ($objects as $object) {
            $datas[] = $hydrator->unhydrate($object);
            $this->clearNullValues($datas);
        }
        
        $res = $collection->insertMany($datas);


        if ($res->isAcknowledged()) {
            foreach($res->getInsertedIds() as $index => $id){
                $hydrator->hydrate($objects[$index], ["_id" => $id]);
                $this->om->setObjectState($objects[$index], ObjectManager::OBJ_MANAGED);
            }
        }
    }

    /**
     * Update object into mongoDB
     * 
     * @param   mixed       $object     Object to update
     */
    private function update($object) {
        $rep = $this->getRepository(get_class($object));
        $collection = $rep->getCollection();

        $diffs = $rep->getObjectChanges($object);
        $update = $this->createUpdateQueryStatement($diffs);

        if (isset($this->modifiers[self::UPDATE_STATEMENT_MODIFIER])) {
            foreach ($this->modifiers[self::UPDATE_STATEMENT_MODIFIER] as $callback) {
                $update = call_user_func($callback, $update);
            }
        }

        if (!empty($update)) {
            $res = $collection->updateOne(["_id" => $object->getId()], $update);
            if ($res->isAcknowledged()) {
                //ACTION IF ACKNOLEDGED
            }
        }
    }

    /**
     * Remove object from MongoDB
     * 
     * @param   mixed       $object     Object to insert
     */
    private function doRemove($object) {
        $rep = $this->getRepository(get_class($object));
        $collection = $rep->getCollection();

        $res = $collection->deleteOne(["_id" => $object->getId()]);

        if ($res->isAcknowledged()) {
            $this->om->removeObject($object);
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
                $update['$set'] += $this->aggregArray($value, $key);
            } else {
                $update['$set'][$key] = $value;
            }
        }

        foreach ($update['$set'] as $key => $value) {
            if (strstr($key, '$push')) {
                unset($update['$set'][$key]);
            }

            if (isset($update['$set'][$key]) && $update['$set'][$key] == null) {
                unset($update['$set'][$key]);
                $update['$unset'][$key] = "";
            }
        }

        if (empty($update['$set'])) {
            unset($update['$set']);
        }

        return $update;
    }

    private function clearNullValues(&$array) {
        foreach ($array as $key => &$value) {
            if (null === $value) {
                unset($array[$key]);
            } else if (is_array($value)) {
                $this->clearNullValues($value);
            }
        }

        return $array;
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

    private function aggregArray($datas, $prefix = '') {
        $new = [];
        foreach ($datas as $key => $value) {
            if (is_a($value, "stdClass")) {
                $value = (array) $value;
            }
            if ($key == '$set') {
                foreach ($value as $k => $val) {
                    $new[$prefix][$k] = $val;
                }
            } else if (is_array($value)) {
                $new += $this->aggregArray($value, $prefix . '.' . $key);
            } else {
                $new[$prefix . '.' . $key] = $value;
            }
        }

        return $new;
    }

}
