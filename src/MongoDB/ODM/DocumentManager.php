<?php

namespace JPC\MongoDB\ODM;

use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;
use JPC\MongoDB\ODM\Exception\ModelNotFoundException;
use axelitus\Patterns\Creational\Singleton;
use JPC\MongoDB\ODM\ObjectManager;
/**
 * For annotations reading
 */
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Annotations\IndexedReader;

/**
 * This allow to interact with document and repositories
 *
 * @author Joffrey Porée <contact@joffreyporee.com>
 */
class DocumentManager extends Singleton {

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
     *
     * @var Tools\ClassMetadataFactory
     */
    private $classMetadataFactory;

    const UPDATE_STATEMENT_MODIFIER = 0;

    /**
     * Modifiers
     * @var array of callable 
     */
    private $modifiers = [];

    function __construct($mongouri, $db, $debug = false) {
        $this->mongoclient = new MongoClient($mongouri);
        $this->mongodatabase = $this->mongoclient->selectDatabase($db);
        $this->classMetadataFactory = Tools\ClassMetadataFactory::instance();
        $this->om = ObjectManager::instance();
    }

    public function addModelPath($identifier, $path) {
        $this->modelPaths[$identifier] = $path;
    }

    function getRepository($modelName, $collection = null) {
        $rep = "JPC\MongoDB\ODM\Repository";
        foreach ($this->modelPaths as $modelPath) {
            if (file_exists($modelPath . "/" . $modelName . ".php")) {
                require_once $modelPath . "/" . $modelName . ".php";
                $class = $this->classMetadataFactory->getMetadataForClass($modelName);
                if (!$class->hasClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document")) {
                    throw new Exception\AnnotationException("Model '$modelName' need to have 'Document' annotation.");
                } else {
                    $docAnnotation = $class->getClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document");
                    $rep = isset($docAnnotation->repositoryClass) ? $docAnnotation->repositoryClass : $rep;
                    $collection = isset($collection) ? $collection : $docAnnotation->collectionName;
                }
                break;
            }
        }

        if (!isset($class)) {
            throw new ModelNotFoundException($modelName);
        }

        return $rep::instance($modelName, $class);
    }

    public function getMongoDBClient() {
        return $this->mongoclient;
    }

    public function getMongoDBDatabase() {
        return $this->mongodatabase;
    }

    public function getReader() {
        return $this->reader;
    }

    public function persist($object) {
        $this->om->addObject($object);
    }

    public function delete($object) {
        $this->om->setObjectState($object, ObjectManager::OBJ_REMOVED);
    }

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
        foreach ($newObjs as $object) {
            $this->insert($object);
        }
    }

    private function insert($object) {
        $rep = $this->getRepository(get_class($object));
        $collection = $rep->getCollection();

        $hydrator = $rep->getHydrator();

        $datas = $hydrator->unhydrate($object);
        $res = $collection->insertOne($datas);

        if ($res->isAcknowledged()) {
            $hydrator->hydrate($object, ["_id" => $res->getInsertedId()]);
            $this->om->setObjectState($object, ObjectManager::OBJ_MANAGED);
        }
    }

    private function update($object) {
        $rep = $this->getRepository(get_class($object));
        $collection = $rep->getCollection();

        $diffs = $rep->getObjectChanges($object);

        $update = $this->createUpdateQueryStatement($diffs);

        if (isset($this->modifiers[self::UPDATE_STATEMENT_MODIFIER])) {
            foreach ($this->modifiers[self::UPDATE_STATEMENT_MODIFIER] as $callback) {
                call_user_func($callback, $update);
            }
        }

        $res = $collection->updateOne(["_id" => $object->getId()], $update);

        if ($res->isAcknowledged()) {
            //ACTION IF ACKNOLEDGED
        }
    }

    private function doRemove($object) {
        $rep = $this->getRepository(get_class($object));
        $collection = $rep->getCollection();

        $res = $collection->deleteOne(["_id" => $object->getId()]);

        if ($res->isAcknowledged()) {
            $this->om->removeObject($object);
        }
    }

    private function createUpdateQueryStatement($datas) {
        $update = [];

        $update['$set'] = [];
        foreach ($datas as $key => $value) {
            $push = null;
            if (is_array($value)) {
                $push = $this->checkPush($value, $key);
                if ($push != null) {
                    foreach ($push as $field => $fieldValue) {
                        $update['$push'][$field] = ['$each' => $fieldValue];
                    }
                } else {
                    $update['$set']  += $this->aggregArray($value, $key);
                }
            } else {
                $update['$set'][$key] = $value;
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

    private function aggregArray($datas, $prefix = '') {
        $new = [];
        foreach ($datas as $key => $value) {
            if (is_array($value)) {
                $new = $this->aggregArray($value, $prefix.'.'.$key);
            } else {
                $new[$prefix.'.'.$key] = $value;
            }
        }
        
        return $new;
    }

}
