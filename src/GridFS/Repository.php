<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\GridFS\Hydrator;
use JPC\MongoDB\ODM\Repository as BaseRep;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use MongoDB\Collection;
use MongoDB\GridFS\Bucket;

/**
 * @author poree
 */
class Repository extends BaseRep {

    /**
     * GridFS Bucket
     * @var \MongoDB\GridFS\Bucket 
     */
    protected $bucket;

    public function __construct(DocumentManager $documentManager, Collection $collection, ClassMetadata $classMetadata, Hydrator $hydrator, QueryCaster $queryCaster = null, UpdateQueryCreator $uqc = null, Bucket $bucket = null) {

        parent::__construct($documentManager, $collection, $classMetadata, $hydrator, $queryCaster, $uqc);

        if(!is_subclass_of($this->modelName, Document::class)){
            throw new MappingException("Model must extends '" . Document::class . "'.");
        }

        $this->bucket = $bucket;
        if(!isset($this->bucket)){
            $this->bucket = $documentManager->getDatabase()->selectGridFSBucket(["bucketName" => $this->classMetadata->getBucketName()]);
        }
    }

    public function getBucket() {
        return $this->bucket;
    }

    public function find($id, $projections = array(), $options = array()) {
        if(null !== ($object = parent::find($id, $projections, $options))){
            $stream = $this->bucket->openDownloadStream($object->getId());
            $object->setStream($stream);
            return $object;
        }
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
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
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
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
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
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }
    }

    /**
     * FindAndModifyOne document
     *
     * @param   array                   $filters            Filters of the query
     * @param   array                   $update             Update of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findAndModifyOne($filters = [], $update = [], $projections = [], $sorts = [], $options = []) {

        $options = array_merge($options, [
            "projection" => $this->castQuery($projections),
            "sort" => $this->castQuery($sorts)
        ]);

        $result = (array) $this->collection->findOneAndUpdate($this->castQuery($filters), $this->castQuery($update), $options);

        if ($result != null) {
            $result = $this->createHytratableResult($result);
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);
            
            $this->documentManager->persist($object, $this->getCollection()->getCollectionName());
            $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
            $this->cacheObject($object);
            return $object;
        }

        return null;
    }
	
	public function drop() {
        $this->bucket->drop();
    }
	
	public function cacheObject($object) {
        if (is_object($object)) {
            $unhyd = $this->hydrator->unhydrate($object);
            unset($unhyd["stream"]);
            $this->objectCache->save(spl_object_hash($object), $unhyd);
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
