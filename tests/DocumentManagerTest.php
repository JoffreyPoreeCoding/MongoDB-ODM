<?php

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\StateException;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Query\BulkWrite;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use MongoDB\Client;
use MongoDB\Database;

class DocumentManagerTest extends TestCase
{

    private $documentManager;

    private $mongoClient;
    private $mongoDatabase;
    private $classMetadataFactory;
    private $repositoryFactory;

    public function setUp()
    {
        $this->mongoClient = $this->createMock(Client::class);
        $this->mongoDatabase = $this->createMock(Database::class);
        $this->repositoryFactory = $this->createMock(RepositoryFactory::class);

        $this->documentManager = new DocumentManager($this->mongoClient, $this->mongoDatabase, $this->repositoryFactory, true);
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
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $repositoryMock->method('getClassMetadata')->willReturn($classMetadataMock);
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
        $repositoryMock->method('getClassMetadata')->willReturn($classMetadataMock);

        $obj1 = new \stdClass();
        $this->documentManager->addObject($obj1, DocumentManager::OBJ_NEW, $repositoryMock);

        $obj2 = new \stdClass();
        $this->documentManager->addObject($obj2, DocumentManager::OBJ_MANAGED, $repositoryMock);

        $obj3 = new \stdClass();
        $this->documentManager->addObject($obj3, DocumentManager::OBJ_REMOVED, $repositoryMock);

        $repositoryMock->expects($this->once())->method('insertOne')->with($obj1, ['getQuery' => true])->willReturn(new \JPC\MongoDB\ODM\Query\InsertOne($this->documentManager, $repositoryMock, $obj1));
        $repositoryMock->expects($this->once())->method('updateOne')->with($obj1, [], ['getQuery' => true])->willReturn(new \JPC\MongoDB\ODM\Query\UpdateOne($this->documentManager, $repositoryMock, $obj2));
        $repositoryMock->expects($this->once())->method('deleteOne')->with($obj1, ['getQuery' => true])->willReturn(new \JPC\MongoDB\ODM\Query\DeleteOne($this->documentManager, $repositoryMock, $obj3));

        $bulkWriteQueryMock = $this->createMock(BulkWrite::class);

        $repositoryMock->method('createBulkWriteQuery')->willReturn($bulkWriteQueryMock);

        $dm = $this->documentManager;
        $bulkWriteQueryMock->method('execute')->will(
            $this->returnCallback(function () use ($dm, $obj1, $obj2, $obj3) {
                $dm->removeObject($obj3);
                $dm->setObjectState($obj1, ObjectManager::OBJ_MANAGED);
            })
        );

        $repositoryMock->expects($this->exactly(3))
            ->method("hasUpdate")
            ->with($this->equalTo($obj2))
            ->will($this->onConsecutiveCalls(true, false, false));

        $this->documentManager->flush();
    }
}
