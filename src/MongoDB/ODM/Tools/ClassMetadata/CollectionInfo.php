<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata;

/**
 * Description of CollectionInfos
 *
 * @author poree
 */
class CollectionInfo {
    private $collection;
    private $writeConcern;
    private $readPreference;
    private $writePreference;
    private $repository;
    
    function getCollection() {
        return $this->collection;
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

    function setCollection($collection) {
        $this->collection = $collection;
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
}
