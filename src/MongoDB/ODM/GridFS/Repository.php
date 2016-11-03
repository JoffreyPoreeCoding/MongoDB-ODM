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
        parent::__construct($documentManager, $objectManager, $classMetadata, $collection);
        
        $this->bucket = $documentManager->getMongoDBDatabase()->selectGridFSBucket(['bucketName' => $collection]);
    }

    public function find($id, $projections = array(), $options = array()) {
        $options = array_merge($options, [
            "projection" => $this->castMongoQuery($projections)
        ]);

        $result = $this->bucket->find(["_id" => $id], $options)->toArray()[0];
        
        dump($result);
        
        dump($this->bucket->openDownloadStream($result->_id));

        if ($result !== null) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $result);

            $this->cacheObject($object);
            $this->objectManager->addObject($object, ObjectManager::OBJ_MANAGED);
            return $object;
        }

        return false;
    }

    public function findAll($projections = array(), $sorts = array(), $options = array()) {
        return parent::findAll($projections, $sorts, $options);
    }

    public function findBy($filters, $projections = array(), $sorts = array(), $options = array()) {
        return parent::findBy($filters, $projections, $sorts, $options);
    }

    public function findOneBy($filters = array(), $projections = array(), $sorts = array(), $options = array()) {
        return parent::findOneBy($filters, $projections, $sorts, $options);
    }

    
}
