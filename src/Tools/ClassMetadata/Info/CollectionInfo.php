<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata\Info;

/**
 * Store collection info for a class
 */
class CollectionInfo
{

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
     * @var bool
     */
    private $bypassConstructorOnFind;

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

    public function getCollection()
    {
        return $this->collectionName;
    }

    public function getBucketName()
    {
        return $this->bucketName;
    }

    public function getWriteConcern()
    {
        return $this->writeConcern;
    }

    public function getReadPreference()
    {
        return $this->readPreference;
    }

    public function getWritePreference()
    {
        return $this->writePreference;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getHydrator()
    {
        return $this->hydrator;
    }

    public function getBypassConstructorOnFind()
    {
        return $this->bypassConstructorOnFind;
    }

    public function getCreationOptions()
    {
        return $this->creationOptions;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setCollection($collection)
    {
        $this->collectionName = $collection;
        return $this;
    }

    public function setBucketName($bucketName)
    {
        $this->bucketName = $bucketName;
    }

    public function setWriteConcern($writeConcern)
    {
        $this->writeConcern = $writeConcern;
        return $this;
    }

    public function setReadPreference($readPreference)
    {
        $this->readPreference = $readPreference;
        return $this;
    }

    public function setWritePreference($writePreference)
    {
        $this->writePreference = $writePreference;
        return $this;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
        return $this;
    }

    public function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    public function setBypassConstructorOnFind(bool $bypassConstructorOnFind)
    {
        $this->bypassConstructorOnFind = $bypassConstructorOnFind;

        return $this;
    }

    public function setCreationOptions($options)
    {
        $this->creationOptions = $options;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }
}
