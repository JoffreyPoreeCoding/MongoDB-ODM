<?php

namespace JPC\MongoDB\ODM;

use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;
use JPC\MongoDB\ODM\Exception\ModelNotFoundException;
use axelitus\Patterns\Creational\Singleton;
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
     *
     * @var Tools\ClassMetadataFactory
     */
    private $classMetadataFactory;

    function __construct($mongouri, $db, $debug = false) {
        $this->mongoclient = new MongoClient($mongouri);
        $this->mongodatabase = $this->mongoclient->selectDatabase($db);
        $this->classMetadataFactory = Tools\ClassMetadataFactory::instance();
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
        
        if(!isset($class)){
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

}
