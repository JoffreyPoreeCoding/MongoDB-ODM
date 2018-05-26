<?php

namespace JPC\Test\MongoDB\ODM\Factory;

use Doctrine\Common\Cache\ArrayCache;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use MongoDB\Collection;
use MongoDB\Database;

class RepositoryFactoryTest extends TestCase
{

    public function testGetRepository()
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $classMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $hydrator = $this->createMock(Hydrator::class);

        $database = $this->createMock(Database::class);
        $database->method("listCollections")->willReturn([]);
        $database->method("selectCollection")->willReturn($this->createMock(Collection::class));

        $documentManager->method("getDatabase")->willReturn($database);

        $classMetadata->method("getRepositoryClass")->willReturn(Repository::class);
        $classMetadata->method("getHydratorClass")->willReturn(get_class($hydrator));
        $classMetadata->method("getCollectionCreationOptions")->willReturn(["capped" => true]);
        $classMetadata->method("getCollectionOptions")->willReturn(["writeConcern" => "majority"]);

        $classMetadataFactory->method("getMetadataForClass")->willReturn($classMetadata);

        $repositoryFactory = new RepositoryFactory(new ArrayCache(), $classMetadataFactory);
        $repository = $repositoryFactory->getRepository($documentManager, "MyModel", "collection");

        $this->assertInstanceOf(Repository::class, $repository);
    }
}
