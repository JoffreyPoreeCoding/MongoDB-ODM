<?php

namespace JPC\MongoDB\ODM\Factory;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use MongoDB\Collection;

class RepositoryFactory {

	private $cache;

	private $classMetadataFactory;

    /**
     * Create new repository factory
     * 
     * @param Cache|null                $cache                Cache used to store repositories
     * @param ClassMetadataFactory|null $classMetadataFactory Factory that will create class Metadata
     */
	public function __construct(Cache $cache = null, ClassMetadataFactory $classMetadataFactory = null){
		$this->cache = isset($cache) ? $cache : new ArrayCache();
		$this->classMetadataFactory = isset($classMetadataFactory) ? $classMetadataFactory : new ClassMetadataFactory();
	}

	public function getRepository(DocumentManager $documentManager, $modelName, $collectionName){
		$repIndex = $modelName . $collectionName;
        if (false != ($repository = $this->cache->fetch($repIndex))) {
            return $repository;
        }

        $classMetadata = $this->classMetadataFactory->getMetadataForClass($modelName);

        if (!isset($collectionName)) {
            $collectionName = $classMetadata->getCollection();
        }

        $repositoryClass = $classMetadata->getRepositoryClass();

        $repIndex = $modelName . $collectionName;
        if (false != ($repository = $this->cache->fetch($repIndex))) {
            return $repository;
        }

        $collection = $this->createCollection($documentManager, $classMetadata, $collectionName);

        $hydratorClass = $classMetadata->getHydratorClass();
        $hydrator = new $hydratorClass($this->classMetadataFactory, $classMetadata);


        $repository = new $repositoryClass($documentManager, $collection, $classMetadata, $hydrator);
        $this->cache->save($repIndex, $repository);

        return $repository;
    }

    /**
     * @TODO
     * @param  DocumentManager $documentManager [description]
     * @param  ClassMetadata   $classMetadata   [description]
     * @param  [type]          $collectionName  [description]
     * @return [type]                           [description]
     */
    private function createCollection(DocumentManager $documentManager, ClassMetadata $classMetadata, $collectionName){

        $database = $documentManager->getDatabase();

        $exists = false;
        foreach ($database->listCollections()as $collection) {
            if ($collection->getName() == $collectionName) {
                $exists = true;
            }
        }

        $creationOptions = $classMetadata->getCollectionCreationOptions();
        if (!empty($creationOptions)) {
            $database->createCollection($collectionName, $creationOptions);
            if($documentManager->getDebug())
                $documentManager->getLogger()->debug("Create collection '$collectionName', see metadata for options", ["options" => $options]);
        }

        $collectionOptions = $classMetadata->getCollectionOptions();

        return $database->selectCollection($collectionName, $collectionOptions);
    }

}