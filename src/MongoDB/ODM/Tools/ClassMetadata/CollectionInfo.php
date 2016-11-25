<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata;

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
     * Repository class name
     * @var string
     */
    private $repository;
    
    /**
     * Options for collection creation
     * @var array 
     */
    private $creationOptions = [];
    
    /**
     * Options for collection constructor
     * @var array
     */
    private $options;
    
    function getCollection() {
        return $this->collectionName;
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
    
    function setCreationOptions($options){
        $this->creationOptions = $options;
    }
    
    function setOptions($options) {
        $this->options = $options;
    }


}
