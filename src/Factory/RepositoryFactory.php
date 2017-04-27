<?php

namespace JPC\MongoDB\ODM\Factory;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use MongoDB\Collection;

class RepositoryFactory {

 protected $cache;

 protected $classMetadataFactory;

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

      if(is_a($repositoryClass, "JPC\MongoDB\ODM\GridFS\Repository", true) && false === strstr($collectionName, ".files")){
        $bucketName = $collectionName;
        $collectionName .= ".files";
      } else if (is_a($repositoryClass, "JPC\MongoDB\ODM\GridFS\Repository", true) && false !== strstr($collectionName, ".files")){
        $bucketName = strstr($collectionName, ".files", true);
      }

      $repIndex = $modelName . $collectionName;
      if (false != ($repository = $this->cache->fetch($repIndex))) {
        return $repository;
      }

      $collection = $this->createCollection($documentManager, $classMetadata, $collectionName);

      $hydratorClass = $classMetadata->getHydratorClass();
      $hydrator = new $hydratorClass($this->classMetadataFactory, $classMetadata, $documentManager, $this);

      $queryCaster = new QueryCaster($classMetadata, $this->classMetadataFactory);

      if(isset($bucketName)){
        $bucket = $documentManager->getDatabase()->selectGridFSBucket(["bucketName" => $bucketName]);

        $repository = new $repositoryClass($documentManager, $collection, $classMetadata, $hydrator, $queryCaster, null, $bucket);
      } else {
        $repository = new $repositoryClass($documentManager, $collection, $classMetadata, $hydrator, $queryCaster);
      }
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
      if (!empty($creationOptions) && !$exists) {
        $database->createCollection($collectionName, $creationOptions);
      }

      $collectionOptions = $classMetadata->getCollectionOptions();

      return $database->selectCollection($collectionName, $collectionOptions);
    }

  }
