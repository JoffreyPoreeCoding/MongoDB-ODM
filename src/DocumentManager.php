<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\Exception\ExtensionException;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\EventManager;
use JPC\MongoDB\ODM\Tools\Logger\LoggerInterface;
use JPC\MongoDB\ODM\Tools\Logger\MemoryLogger;
use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;

/**
 * This allow to interact with document and repositories
 *
 * @author Joffrey Porée <contact@joffreyporee.com>
 */
class DocumentManager extends ObjectManager
{

    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

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
     * Store repository associated with object (for flush on special collection)
     * @var array
     */
    private $objectsRepository = [];

    /**
     * Factory of repository
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * Logger object
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Default options for repositories
     * @var array
     */
    protected $defaultOptions = [];

    /* ================================== */
    /*          PUBLICS FUNCTIONS         */
    /* ================================== */

    /**
     * Create new DocumentManager
     * @param MongoClient               $client             MongoClient for connection
     * @param MongoDatabase             $database           MongoDatabase object
     * @param RepositoryFactory|null    $repositoryFactory  RepositoryFactory object
     * @param LoggerInterface           $logger             A logger that implement the LoggerInterface
     * @param boolean                   $debug              Enable or not the debug mode
     * @param array                     $defaultOptions     Default options for commands
     */
    public function __construct(
        MongoClient $client,
        MongoDatabase $database,
        RepositoryFactory $repositoryFactory = null,
        LoggerInterface $logger = null,
        $debug = false,
        $defaultOptions = []
    ) {
        $this->debug = $debug;
        if ($this->debug) {
            apcu_clear_cache();
        }

        if (isset($logger) && !$logger instanceof LoggerInterface) {
            throw new \Exception("Logger must implements '" . LoggerInterface::class . "'");
        }

        $this->logger = !isset($logger) ? new MemoryLogger() : $logger;
        $this->client = $client;
        $this->database = $database;
        $this->repositoryFactory = isset($repositoryFactory) ? $repositoryFactory : new RepositoryFactory();
        $this->objectManager = isset($objectManager) ? $objectManager : new ObjectManager();
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
     * Persist an object in object manager
     *
     * @param   mixed       $object     Object to persist
     */
    public function persist($object, $collection = null)
    {
        $repository = $this->getRepository(get_class($object), $collection);
        $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_PRE_PERSIST, $object);
        $this->addObject($object, ObjectManager::OBJ_NEW, $repository);
        $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_POST_PERSIST, $object);
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
     * Add a managed object
     *
     * @param   object          $object         Object to manage
     * @param   Repository      $repository     Repository
     * @param   integer         $state          State of managed object
     */
    public function addObject($object, $state = self::OBJ_NEW, $repository = null)
    {
        parent::addObject($object, $state);
        $this->objectsRepository[spl_object_hash($object)] = $repository;
    }

    /**
     * Remove object from object manager
     *
     * @param  object           $object         Object to remove
     */
    public function removeObject($object)
    {
        parent::removeObject($object);
        unset($this->objectsRepository[spl_object_hash($object)]);
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

        $mongoCollection = $repository->getCollection();

        $id = $repository->getHydrator()->unhydrate($object)["_id"];

        if ($this->debug) {
            $this->logger->debug("Refresh datas for object with id " . (string) $id . ' in collection ' . $mongoCollection);
        }

        $datas = (array) $mongoCollection->findOne(["_id" => $id]);

        if ($datas != null) {
            $repository->getHydrator()->hydrate($object, $datas);
            $repository->cacheObject($object);
        } else {
            $object = null;
        }
    }

    /**
     * Flush all changes and write it in mongoDB
     */
    public function flush()
    {
        if ($this->debug) {
            $countRemove = count($this->getObject(ObjectManager::OBJ_REMOVED));
            $countUpdate = count($this->getObject(ObjectManager::OBJ_MANAGED));
            $countInsert = count($this->getObject(ObjectManager::OBJ_NEW));
            $this->logger->debug("Flushing datas to database, $countInsert to insert, $countUpdate to update, $countRemove to remove.");
        }

        $removeObjs = $this->getObject(ObjectManager::OBJ_REMOVED);
        foreach ($removeObjs as $object) {
            $repository = $this->objectsRepository[spl_object_hash($object)];
            $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_PRE_FLUSH, $object);
            $repository->deleteOne($object);
            $this->removeObject($object);
            $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_POST_FLUSH, $object);
        }

        $updateObjs = $this->getObject(ObjectManager::OBJ_MANAGED);
        foreach ($updateObjs as $object) {
            $repository = $this->objectsRepository[spl_object_hash($object)];
            $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_PRE_FLUSH, $object);
            $repository->updateOne($object);
            $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_POST_FLUSH, $object);
        }

        $newObjs = $this->getObject(ObjectManager::OBJ_NEW);

        $toInsert = [];
        $repositories = [];
        foreach ($newObjs as $object) {
            $repository = $this->objectsRepository[spl_object_hash($object)];
            $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_PRE_FLUSH, $object);
            $rid = spl_object_hash($repository);
            $toInsert[$rid][] = $object;
            isset($repositories[$rid]) ?: $repositories[$rid] = $repository;
        }

        foreach ($toInsert as $repository => $objects) {
            $repository = $repositories[$repository];
            $repository->insertMany($objects);
            foreach ($objects as $object) {
                $this->setObjectState($object, self::OBJ_MANAGED);
                $repository->getClassMetadata()->getEventManager()->execute(EventManager::EVENT_POST_FLUSH, $object);
            }
        }
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

    /* ------------------------------- */
    /*        GETTERS / SETTERS        */
    /* ------------------------------- */

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
     * Gets the Logger object.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the Logger object.
     *
     * @param LoggerInterface $logger the logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

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
}
