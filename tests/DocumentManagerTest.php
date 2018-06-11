<?php

namespace JPC\Test\MongoDB\ODM;

use MongoDB\Client;
use MongoDB\Database;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Tools\EventManager;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use JPC\MongoDB\ODM\Exception\StateException;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\Tools\Logger\LoggerInterface;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class DocumentManagerTest extends TestCase
{

    private $documentManager;

    private $mongoClient;
    private $mongoDatabase;
    private $classMetadataFactory;
    private $repositoryFactory;
    private $logger;

    public function setUp()
    {
        $this->mongoClient = $this->createMock(Client::class);
        $this->mongoDatabase = $this->createMock(Database::class);
        $this->repositoryFactory = $this->createMock(RepositoryFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->documentManager = new DocumentManager($this->mongoClient, $this->mongoDatabase, $this->repositoryFactory, $this->logger, true);
    }

    public function testGetRepository()
    {
        $this->repositoryFactory->method("getRepository")->willReturn("repository");

        $rep = $this->documentManager->getRepository("My\Fake\Class");

        $this->assertEquals("repository", $rep);
    }

    /**
     * @test
     * unpersist
     */
    public function testPersistUnpersist()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $eventManagerMock = $this->createMock(EventManager::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $repositoryMock->method('getClassMetadata')->willReturn($classMetadataMock);
        $classMetadataMock->method('getEventManager')->willReturn($eventManagerMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $this->repositoryFactory->method("getRepository")->willReturn($repositoryMock);
        $object = new \stdClass();
        $this->documentManager->persist($object);

        $oid = spl_object_hash($object);
        $this->assertArrayHasKey($oid, $this->documentManager->getObjects());
        $this->assertContains($object, $this->documentManager->getObjects());

        $repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
        $this->assertArrayHasKey($oid, $repositories);
        $this->assertEquals($repositoryMock, $repositories[$oid]);

        $this->documentManager->unpersist($object);

        $this->assertArrayNotHasKey($oid, $this->documentManager->getObjects());
        $this->assertNotContains($object, $this->documentManager->getObjects());

        $repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
        $this->assertArrayNotHasKey($oid, $repositories);
        $this->assertEmpty($repositories);
    }

    public function testAddObjectRemoveObject()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();
        $this->documentManager->addObject($object, DocumentManager::OBJ_NEW, $repositoryMock);

        $oid = spl_object_hash($object);
        $this->assertArrayHasKey($oid, $this->documentManager->getObjects());
        $this->assertContains($object, $this->documentManager->getObjects());

        $repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
        $this->assertArrayHasKey($oid, $repositories);
        $this->assertEquals($repositoryMock, $repositories[$oid]);

        $this->documentManager->removeObject($object);

        $this->assertArrayNotHasKey($oid, $this->documentManager->getObjects());
        $this->assertNotContains($object, $this->documentManager->getObjects());

        $repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
        $this->assertArrayNotHasKey($oid, $repositories);
        $this->assertEmpty($repositories);
    }

    public function testDeleteClear()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();

        $this->documentManager->addObject($object, DocumentManager::OBJ_MANAGED, $repositoryMock);

        $oid = spl_object_hash($object);
        $this->assertArrayHasKey($oid, $this->documentManager->getObjects());
        $this->assertContains($object, $this->documentManager->getObjects());

        $repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
        $this->assertArrayHasKey($oid, $repositories);
        $this->assertEquals($repositoryMock, $repositories[$oid]);

        $this->documentManager->remove($object);
        $this->assertArrayHasKey($oid, $this->documentManager->getObjects(DocumentManager::OBJ_REMOVED));
        $this->assertContains($object, $this->documentManager->getObjects(DocumentManager::OBJ_REMOVED));

        $this->documentManager->clear();
        $this->documentManager->addObject($object, DocumentManager::OBJ_NEW, $repositoryMock);
        $this->expectException(StateException::class);
        $this->documentManager->remove($object);
    }

    public function testFlush()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $eventManagerMock = $this->createMock(EventManager::class);
        $repositoryMock->method('getClassMetadata')->willReturn($classMetadataMock);
        $classMetadataMock->method('getEventManager')->willReturn($eventManagerMock);

        $obj1 = new \stdClass();
        $this->documentManager->addObject($obj1, DocumentManager::OBJ_NEW, $repositoryMock);

        $obj2 = new \stdClass();
        $this->documentManager->addObject($obj2, DocumentManager::OBJ_MANAGED, $repositoryMock);

        $obj3 = new \stdClass();
        $this->documentManager->addObject($obj3, DocumentManager::OBJ_REMOVED, $repositoryMock);

        $repositoryMock->expects($this->once())
            ->method("deleteOne")
            ->with($obj3);
        $repositoryMock->expects($this->exactly(3))
            ->method("hasUpdate")
            ->with($this->equalTo($obj2))
            ->will($this->onConsecutiveCalls(true, false, false));
        $repositoryMock->expects($this->once())
            ->method("updateOne")
            ->with($obj2);
        $repositoryMock->expects($this->once())
            ->method("insertMany")
            ->with([$obj1]);

        $this->documentManager->flush();
    }
}
