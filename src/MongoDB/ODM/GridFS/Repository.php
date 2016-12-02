<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\Repository as BaseRep;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\ObjectManager;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Description of GridFSRepository
 *
 * @author poree
 */
class Repository extends BaseRep {

    /**
     * 
     * @var \MongoDB\GridFS\Bucket 
     */
    protected $bucket;

    public function __construct(DocumentManager $documentManager, ObjectManager $objectManager, $classMetadata, $collection) {
        $this->bucket = $documentManager->getMongoDBDatabase()->selectGridFSBucket(['bucketName' => $collection]);
		
		$this->classMetadata = $classMetadata;

        $this->documentManager = $documentManager;
        $this->modelName = $classMetadata->getName();
        $this->hydrator = Hydrator::getInstance($this->modelName . spl_object_hash($documentManager), $documentManager, $classMetadata);

        $this->collection = $this->documentManager->getMongoDBDatabase()->selectCollection($collection . ".files");

        $this->objectManager = $objectManager;
        $this->objectCache = new ArrayCache();
    }

    public function getBucket() {
        return $this->bucket;
    }

    public function find($id, $projections = array(), $options = array()) {
        if (!empty($projections) && isset($projections["_id"]) && !$projections["_id"]) {
            $projections["_id"] = true;
        }

        $options = array_merge($options, [
            "projection" => $this->castQuery($projections)
        ]);

        $result = (array) $this->collection->findOne(["_id" => $id], $options);


        if (!empty($result)) {
            $result = $this->createHytratableResult($result);
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);

            $this->cacheObject($object);
            $this->objectManager->addObject($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }

        return false;
    }

    public function findAll($projections = array(), $sorts = array(), $options = array()) {
        if (!empty($projections) && isset($projections["_id"]) && !$projections["_id"]) {
            $projections["_id"] = true;
        }
        $options = array_merge($options, [
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
        ]);

        $result = $this->collection->find([], $options);

        $objects = [];

        foreach ($result as $datas) {
            $datas = $this->createHytratableResult($datas);
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $this->cacheObject($object);
            $this->objectManager->addObject($object, ObjectManager::OBJ_MANAGED);
            $objects[] = $object;
        }

        return $objects;
    }

    public function findBy($filters, $projections = array(), $sorts = array(), $options = array()) {
        if (!empty($projections) && isset($projections["_id"]) && !$projections["_id"]) {
            $projections["_id"] = true;
        }
        $options = array_merge($options, [
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
        ]);

        $result = $this->collection->find($this->castQuery($filters), $options);

        $objects = [];

        foreach ($result as $datas) {
            $datas = $this->createHytratableResult($datas);
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $this->cacheObject($object);
            $this->objectManager->addObject($object, ObjectManager::OBJ_MANAGED);
            $objects[] = $object;
        }

        return $objects;
    }

    public function findOneBy($filters = array(), $projections = array(), $sorts = array(), $options = array()) {
        if (!empty($projections) && isset($projections["_id"]) && !$projections["_id"]) {
            $projections["_id"] = true;
        }

        $options = array_merge($options, [
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
        ]);

        $result = (array) $this->collection->findOne($this->castQuery($filters), $options);
        
        if (!empty($result)) {
            $result = $this->createHytratableResult($result);
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);

            $this->cacheObject($object);
            $this->objectManager->addObject($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }
    }

    public function createHytratableResult($result) {
        $newResult = $result;
        
        if (isset($result["metadata"])) {
            foreach ($result["metadata"] as $key => $value) {
                $newResult[$key] = $value;
            }
            
            unset($newResult["metadata"]);
        }

        $stream = $this->bucket->openDownloadStream($result["_id"]);
        $newResult["stream"] = $stream;

        return $newResult;
    }

}
