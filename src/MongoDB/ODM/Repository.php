<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use axelitus\Patterns\Creational\Multiton;

/**
 * Allow to find, delete, document in MongoDB
 *
 * @author poree
 */
class Repository extends Multiton{

    /**
     * Model name
     * @var string
     */
    private $modelName;

    /**
     * Mongo Db Collection
     * @var Collection
     */
    private $collection;
    
    /**
     * Hydrator of model
     * @var Hydrator
     */
    private $hydrator;

    /**
     * Annotation Reader
     * @var Doctrine\Common\Annotations\CachedReader
     */
    private $reader;

    /**
     * Class reflection
     * @var ReflectionClass 
     */
    private $reflectionClass;

    public function __construct($modelName, $collection, $reflectionClass) {
        $this->modelName = $modelName;
        
        $this->hydrator = Hydrator::instance($modelName, DocumentManager::instance()->getReader(), $reflectionClass);

        $this->collection = DocumentManager::instance()->getMongoDBDatabase()->selectCollection($collection);
    }
    
    public function getHydrator(){
        return $this->hydrator;
    }

    public function count($filters = [], $options = []) {
        return $this->collection->count($filters, $options);
    }

    public function find($id, $options = [], $autopersist = true) {
        $result = $this->collection->findOne(["_id" => $id], $options);
        
        $object = new $this->modelName();
        $this->hydrator->hydrate($object, $result);
        
        return $object;
    }
    
    public function findAll($options = [], $autopersist = true){
        $result = $this->collection->find([], $options);
        
        $objects = [];
        
        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $objects[] = $object;
        }
        
        return $objects;
    }
    
    public function findBy($filters, $options = [], $autopersist = true){
        $result = $this->collection->find($filters, $options);
        
        $objects = [];
        
        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $objects[] = $object;
        }
        
        return $objects;
    }
    
    public function findOneBy($filter = [], $options = [], $autopersist = true) {
        $result = $this->collection->findOne($filter, $options);
        
        $object = new $this->modelName();
        $this->hydrator->hydrate($object, $result);
        
        return $object;
    }
    
    public function distinct($fieldName, $filters = [], $options = []){
        $result = $this->collection->distinct($fieldName, $filters, $options);
        
        return $result;
    }
    
    public function drop(){
        $result = $this->collection->drop();
        
        if($result->ok){
            return true;
        } else {
            return false;
        }
    }
}
