<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\Event\ModelEvent\PostFlushEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostPersistEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreFlushEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PrePersistEvent;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\GridFS\Repository as GridFSRepository;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Repository;
use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * MongoDB Documents manager
 */
class DocumentManager extends ObjectManager
{

    /**
     * Debug mode activated or not
     *
     * @var boolean
     */
    private $debug;

    /**
     * MongoDB Connection
     * @var MongoClient
     */
    private $client;

    /**
     * MongoDB Database
     * @var MongoDatabase
     */
    private $database;

    /**
     * Factory of repository
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * Default options for repositories
     * @var array
     */
    protected $options = [];

    /**
     * Default options for repositories
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Dispatcher for customizable events
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Create new DocumentManager
     * @param MongoClient               $client             MongoClient for connection
     * @param MongoDatabase             $database           MongoDatabase object
     * @param RepositoryFactory|null    $repositoryFactory  RepositoryFactory object
     * @param boolean                   $debug              Enable or not the debug mode
     * @param array                     $options            ODM Options
     * @param array                     $defaultOptions     Default options for commands
     * @param EventDispatcher           $eventDispatcher    Dispatcher for customizable events
     *
     * Available options :
     *  * hydratorStrategy : Hydrator::SETTERS, Hydrator::ATTRIBUTES
     *
     */
    public function __construct(
        MongoClient $client,
        MongoDatabase $database,
        RepositoryFactory $repositoryFactory = null,
        $debug = false,
        $options = [],
        $defaultOptions = [],
        EventDispatcher $eventDispatcher = null
    ) {
        $this->debug = $debug;
        if ($this->debug) {
            apcu_clear_cache();
        }

        $this->options = $options;
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->client = $client;
        $this->database = $database;
        $this->repositoryFactory = isset($repositoryFactory) ? $repositoryFactory : new RepositoryFactory($this->eventDispatcher);
        $this->objectManager = new ObjectManager();
    }

    /**
     * Allow to get repository for specified model
     *
     * @param   string      $modelName  Name of the model
     * @param   string      $collection Name of the collection (null for get collection from document annotation)
     *
     * @return  Repository  Repository for model
     */
    public function getRepository($modelName, $collection = null)
    {
        return $this->repositoryFactory->getRepository($this, $modelName, $collection);
    }

    /**
     * Persist object in document manager
     *
     * @param   mixed   $object Object to persist
     * @param   string  $collection Collection where object is persisted
     * @return  void
     */
    public function persist($object, $collection = null)
    {
        $repository = $this->getRepository(get_class($object), $collection);
        $event = new PrePersistEvent($this, $repository, $object);
        $this->eventDispatcher->dispatch($event, $event::NAME);
        $this->addObject($object, ObjectManager::OBJ_NEW, $repository);
        $event = new PostPersistEvent($this, $repository, $object);
        $this->eventDispatcher->dispatch($event, $event::NAME);

        return $this;
    }

    /**
     * Unpersist an object in object Manager
     *
     * @param   mixed       $object     Object to unpersist
     */
    public function unpersist($object)
    {
        $this->removeObject($object);
    }

    /**
     * Set object to be deleted at next flush
     *
     * @param   mixed       $object     Object to delete
     */
    public function delete($object)
    {
        $this->setObjectState($object, self::OBJ_REMOVED);
    }

    /**
     * Set object to be deleted at next flush
     *
     * @param   mixed       $object     Object to delete
     */
    public function remove($object)
    {
        $this->setObjectState($object, self::OBJ_REMOVED);
    }

    /**
     * Refresh an object to last MongoDB values
     *
     * @param   mixed       $object     Object to refresh
     */
    public function refresh(&$object)
    {
        if (!isset($this->objectsRepository[spl_object_hash($object)])) {
            return;
        }
        $repository = $this->objectsRepository[spl_object_hash($object)];
        $id = $repository->getHydrator()->unhydrate($object)["_id"];
        $projection = $repository->getLastProjection($object);
        $collection = $repository->getCollection();
        $hydrator = $repository->getHydrator();

        if (null !== ($data = $collection->findOne(['_id' => $id], ['projection' => $projection]))) {
            $hydrator->hydrate($object, $data);
            $repository->cacheObject($object);
        } else {
            $object = null;
        }
        return $object;
    }

    public function flush()
    {
        $removeObjs = $this->getObjects(ObjectManager::OBJ_REMOVED);
        $updateObjs = $this->getObjects(ObjectManager::OBJ_MANAGED);
        $newObjs = $this->getObjects(ObjectManager::OBJ_NEW);

        foreach ($updateObjs as $key => $object) {
            $repository = $this->objectsRepository[spl_object_hash($object)];
            if (!$repository->hasUpdate($object)) {
                unset($updateObjs[$key]);
            }
        }

        if (!empty(array_merge($newObjs, $updateObjs, $removeObjs))) {
            $this->perfomOperations($newObjs, $updateObjs, $removeObjs);
        }
    }

    /**
     * Perfom operation on collections
     *
     * @param   array   $insert     Objects to insert
     * @param   array   $update     Objects to update
     * @param   array   $remove     Objects to remove
     * @return  void
     */
    private function perfomOperations($insert, $update, $remove)
    {
        $bulkOperations = [];
        foreach ($insert as $id => $document) {
            $repository = $this->getObjectRepository($document);
            if ($repository instanceof GridFSRepository) {
                $repository->insertOne($document);
                unset($insert[$id]);
            } else {
                $query = $repository->insertOne($document, ['getQuery' => true]);
                $repositoryId = spl_object_hash($repository);
                $bulkOperations[$repositoryId] = isset($bulkOperations[$repositoryId]) ? $bulkOperations[$repositoryId] : $repository->createBulkWriteQuery();
                $bulkOperations[$repositoryId]->addQuery($query);
            }
        }

        foreach ($update as $id => $document) {
            $repository = $this->getObjectRepository($document);
            $query = $repository->updateOne($document, [], ['getQuery' => true]);
            $repositoryId = spl_object_hash($repository);
            $bulkOperations[$repositoryId] = isset($bulkOperations[$repositoryId]) ? $bulkOperations[$repositoryId] : $repository->createBulkWriteQuery();
            $bulkOperations[$repositoryId]->addQuery($query);
        }

        foreach ($remove as $id => $document) {
            $repository = $this->getObjectRepository($document);
            if ($repository instanceof GridFSRepository) {
                $repository->deleteOne($document);
                unset($remove[$id]);
            } else {
                $query = $repository->deleteOne($document, ['getQuery' => true]);
                $repositoryId = spl_object_hash($repository);
                $bulkOperations[$repositoryId] = isset($bulkOperations[$repositoryId]) ? $bulkOperations[$repositoryId] : $repository->createBulkWriteQuery();
                $bulkOperations[$repositoryId]->addQuery($query);
            }
        }

        foreach (array_merge($insert, $update, $remove) as $id => $document) {
            $repository = $this->getObjectRepository($document);
            $event = new PreFlushEvent($this, $repository, $document);
            $this->eventDispatcher->dispatch($event, $event::NAME);
        }

        foreach ($bulkOperations as $bulkOperation) {
            $bulkOperation->execute();
        }

        foreach (array_merge($insert, $update) as $id => $document) {
            $repository = $this->getObjectRepository($document);
            $event = new PostFlushEvent($this, $repository, $document);
            $this->eventDispatcher->dispatch($event, $event::NAME);
            $repository->cacheObject($document);
        }

        $this->flush();
    }

    /**
     * Unmanaged (unpersist) all object
     */
    public function clear()
    {
        foreach ($this->objectsRepository as $repository) {
            if ($repository instanceof Repository) {
                $repository->clear();
            }
        }
        parent::clear();
        $this->objectsRepository = [];
    }

    /**
     * Select database from name
     *
     * @param  string       $name       Database name
     *
     * @return self
     */
    public function selectDatabase($name)
    {
        $this->database = $this->client->selectDatabase($name);
        return $this;
    }

    /**
     * Gets the value of debug.
     *
     * @return mixed
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Sets the value of debug.
     *
     * @param mixed $debug the debug
     *
     * @return self
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Gets the MongoDB Connection.
     *
     * @return MongoClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the MongoDB Connection.
     *
     * @param MongoClient $client the client
     *
     * @return self
     */
    public function setClient(MongoClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gets the MongoDB Database.
     *
     * @return MongoDatabase
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Sets the MongoDB Database.
     *
     * @param MongoDatabase $database the database
     *
     * @return self
     */
    public function setDatabase(MongoDatabase $database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Gets the Store repository associated with object (for flush on special collection).
     *
     * @return array
     */
    public function getObjectsRepository()
    {
        return $this->objectsRepository;
    }

    /**
     * Sets the Store repository associated with object (for flush on special collection).
     *
     * @param array $objectsRepository the objects repository
     *
     * @return self
     */
    public function setObjectsRepository(array $objectsRepository)
    {
        $this->objectsRepository = $objectsRepository;

        return $this;
    }

    /**
     * Gets the Factory of repository.
     *
     * @return RepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return $this->repositoryFactory;
    }

    /**
     * Sets the Factory of repository.
     *
     * @param RepositoryFactory $repositoryFactory the repository factory
     *
     * @return self
     */
    public function setRepositoryFactory(RepositoryFactory $repositoryFactory)
    {
        $this->repositoryFactory = $repositoryFactory;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * @param array $defaultOptions
     *
     * @return self
     */
    public function setDefaultOptions(array $defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $Options
     *
     * @return self
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get dispatcher for customizable events
     *
     * @return  EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
}
