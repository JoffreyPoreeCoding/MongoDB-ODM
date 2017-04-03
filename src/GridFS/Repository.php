<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\GridFS\Hydrator;
use JPC\MongoDB\ODM\Repository as BaseRepository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use MongoDB\Collection;
use MongoDB\GridFS\Bucket;

/**
 * @author poree
 */
class Repository extends BaseRepository {

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
        $objects = parent::findAll($projections, $sorts, $options);
        foreach($objects as $object){
            $stream = $this->bucket->openDownloadStream($object->getId());
            $object->setStream($stream);
        }
        return $objects;
    }

    public function findBy($filters, $projections = array(), $sorts = array(), $options = array()) {
        $objects = parent::findBy($filters, $projections, $sorts, $options);
        foreach($objects as $object){
            $stream = $this->bucket->openDownloadStream($object->getId());
            $object->setStream($stream);
        }
        return $objects;
    }

    public function findOneBy($filters = array(), $projections = array(), $sorts = array(), $options = array()) {
        $object = parent::findOneBy($filters, $projections, $sorts, $options);
        
        if (isset($object)) {
            $stream = $this->bucket->openDownloadStream($object->getId());
            $object->setStream($stream);
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
    public function findAndModifyOneBy($filters = [], $update = [], $projections = [], $sorts = [], $options = []) {
        $object = parent::findAndModifyOneBy($filters, $update, $projections, $sorts, $options);

        if (isset($object)) {
            $stream = $this->bucket->openDownloadStream($object->getId());
            $object->setStream($stream);
            return $object;
        }
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

    public function insertOne($document, $options = []){
        $objectDatas = $this->hydrator->unhydrate($document);

        $stream = $objectDatas["stream"];
        unset($objectDatas["stream"]);

        if(!isset($objectDatas["filename"])){
            $filename = stream_get_meta_data($stream)["uri"];
        } else {
            $filename = $objectDatas["filename"];
        }

        unset($objectDatas["filename"]);

        $this->bucket->uploadFromStream($filename, $stream, $objectDatas);

        return true;
    }

    public function insertMany($documents, $options = []){
        foreach($documents as $document){
            if(!$this->insertOne($document)){
                return false;
            }
        }

        return true;
    }

    public function deleteOne($document, $options = []){
        $unhydratedObject = $this->hydrator->unhydrate($document);

        $id = $unhydratedObject["_id"];

        $this->bucket->delete($id);
    }

    public function deleteMany($filter, $options = []){
        throw new \JPC\MongoDB\ODM\GridFS\Exception\DeleteManyException();
    }

    protected function getUpdateQuery($document){
        $updateQuery = [];
        $old = $this->uncacheObject($document);
        $new = $this->hydrator->unhydrate($document);
        unset($new["stream"]);

        return $this->updateQueryCreator->createUpdateQuery($old, $new);
    }
}
