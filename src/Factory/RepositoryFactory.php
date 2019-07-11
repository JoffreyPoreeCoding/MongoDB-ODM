<?php

namespace JPC\MongoDB\ODM\Factory;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use MongoDB\Collection;

/**
 * Repository factory
 */
class RepositoryFactory
{

    /**
     * Already created repositories
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Class metadata factory
     *
     * @var ClassMetadataFactory
     */
    protected $classMetadataFactory;

    /**
     * Create new repository factory
     *
     * @param Cache|null                $cache                Cache used to store repositories
     * @param ClassMetadataFactory|null $classMetadataFactory Factory that will create class Metadata
     */
    public function __construct(Cache $cache = null, ClassMetadataFactory $classMetadataFactory = null)
    {
        $this->cache = isset($cache) ? $cache : new ArrayCache();
        $this->classMetadataFactory = isset($classMetadataFactory) ? $classMetadataFactory : new ClassMetadataFactory();
    }

    /**
     * Get a repository
     *
     * @param   DocumentManager     $documentManager    Document manager
     * @param   string              $modelName          Class of the model
     * @param   string              $collectionName     Name of MongoDB Collection
     * @param   CacheProvider       $repositoryCache    Cache for the repository
     * @return  Repository
     */
    public function getRepository(DocumentManager $documentManager, $modelName, $collectionName, CacheProvider $repositoryCache = null)
    {
        $repIndex = $modelName . $collectionName;
        if (false != ($repository = $this->cache->fetch($repIndex))) {
            return $repository;
        }

        $classMetadata = $this->classMetadataFactory->getMetadataForClass($modelName);

        if (!isset($collectionName)) {
            $collectionName = $classMetadata->getCollection();
        }

        $repositoryClass = $classMetadata->getRepositoryClass();

        if (is_a($repositoryClass, "JPC\MongoDB\ODM\GridFS\Repository", true) && false === strstr($collectionName, ".files")) {
            $bucketName = $collectionName;
            $collectionName .= ".files";
        } else if (is_a($repositoryClass, "JPC\MongoDB\ODM\GridFS\Repository", true) && false !== strstr($collectionName, ".files")) {
            $bucketName = strstr($collectionName, ".files", true);
        }

        $repIndex = $modelName . $collectionName;
        if (false != ($repository = $this->cache->fetch($repIndex))) {
            return $repository;
        }

        $collection = $this->createCollection($documentManager, $classMetadata, $collectionName);

        $hydratorClass = $classMetadata->getHydratorClass();
        if (!isset($hydratorClass)) {
            throw new \Exception($classMetadata->getName() . ' is not a valid model class. Maybe it doesn\'t have a "Document" annotation.');
        }
        $hydrator = new $hydratorClass($this->classMetadataFactory, $classMetadata, $documentManager, $this);

        $queryCaster = new QueryCaster($classMetadata, $this->classMetadataFactory);

        if (isset($bucketName)) {
            $bucket = $documentManager->getDatabase()->selectGridFSBucket(["bucketName" => $bucketName]);

            $repository = new $repositoryClass($documentManager, $collection, $classMetadata, $hydrator, $queryCaster, null, $repositoryCache, $bucket);
        } else {
            $repository = new $repositoryClass($documentManager, $collection, $classMetadata, $hydrator, $queryCaster, null, $repositoryCache);
        }
        $this->cache->save($repIndex, $repository, 120);

        return $repository;
    }

    /**
     * Create the collection
     *
     * @param   DocumentManager     $documentManager    Current document manager
     * @param   ClassMetadata       $classMetadata      Class metadata
     * @param   string              $collectionName     Name of collection
     * @return  Collection
     */
    private function createCollection(DocumentManager $documentManager, ClassMetadata $classMetadata, $collectionName)
    {

        $database = $documentManager->getDatabase();

        $exists = false;
        foreach ($database->listCollections() as $collection) {
            if ($collection->getName() == $collectionName) {
                $exists = true;
            }
        }

        $creationOptions = $classMetadata->getCollectionCreationOptions();
        if (!empty($creationOptions) && !$exists) {
            $database->createCollection($collectionName, $creationOptions);
        }

        $collectionOptions = $classMetadata->getCollectionOptions();

        return $database->selectCollection($collectionName, $collectionOptions);
    }
}
