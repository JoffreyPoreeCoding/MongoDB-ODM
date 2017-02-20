<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata\Info;

/**
 * Description of CollectionInfos
 *
 * @author poree
 */
class CollectionInfo {
    
    /**
     * Collection Name
     * @var string 
     */
    private $collectionName;

    /**
     * Bucket name
     * @var string
     */
    private $bucketName;
    
    /**
     * Repository class name
     * @var string
     */
    private $repository;

    /**
     * Hydrator class name
     * @var string
     */
    private $hydrator;
    
    /**
     * Options for collection creation
     * @var array 
     */
    private $creationOptions = [];
    
    /**
     * Options for collection constructor
     * @var array
     */
    private $options = [];
    
    function getCollection() {
        return $this->collectionName;
    }

    function getBucketName() {
        return $this->bucketName;
    }

    function getWriteConcern() {
        return $this->writeConcern;
    }

    function getReadPreference() {
        return $this->readPreference;
    }

    function getWritePreference() {
        return $this->writePreference;
    }

    function getRepository() {
        return $this->repository;
    }

    function getHydrator()
    {
        return $this->hydrator;
    }
    
    function getCreationOptions(){
        return $this->creationOptions;
    }

    function getOptions() {
        return $this->options;
    }
    
    function setCollection($collection) {
        $this->collectionName = $collection;
        return $this;
    }

    function setBucketName($bucketName){
        $this->bucketName = $bucketName;
    }

    function setWriteConcern($writeConcern) {
        $this->writeConcern = $writeConcern;
        return $this;
    }

    function setReadPreference($readPreference) {
        $this->readPreference = $readPreference;
        return $this;
    }

    function setWritePreference($writePreference) {
        $this->writePreference = $writePreference;
        return $this;
    }

    function setRepository($repository) {
        $this->repository = $repository;
        return $this;
    }

    function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
        return $this;
    }
    
    function setCreationOptions($options){
        $this->creationOptions = $options;
        return $this;
    }
    
    function setOptions($options) {
        $this->options = $options;
        return $this;
    }
}
