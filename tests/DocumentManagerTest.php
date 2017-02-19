<?php

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\StateException;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\Logger\LoggerInterface;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use MongoDB\Client;
use MongoDB\Database;

class DocumentManagerTest extends TestCase {

	private $documentManager;

	private $mongoClient;
	private $mongoDatabase;
	private $classMetadataFactory;
	private $repositoryFactory;
	private $logger;

	public function setUp(){
		$this->mongoClient = $this->createMock(Client::class);
		$this->mongoDatabase = $this->createMock(Database::class);
		$this->repositoryFactory = $this->createMock(RepositoryFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->documentManager = new DocumentManager($this->mongoClient, $this->mongoDatabase, $this->repositoryFactory, $this->logger, true);
	}

	public function test_getRepository(){
		$this->repositoryFactory->method("getRepository")->willReturn("repository");

		$rep = $this->documentManager->getRepository("My\Fake\Class");

		$this->assertEquals("repository", $rep);
	}

	public function test_persist_unpersist(){
		$this->repositoryFactory->method("getRepository")->willReturn("repository");
		$object = new \stdClass();
		$this->documentManager->persist($object);

		$oid = spl_object_hash($object);
		$this->assertArrayHasKey($oid, $this->documentManager->getObject());
		$this->assertContains($object, $this->documentManager->getObject());

		$repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
		$this->assertArrayHasKey($oid, $repositories);
		$this->assertEquals("repository", $repositories[$oid]);

		$this->documentManager->unpersist($object);

		$this->assertArrayNotHasKey($oid, $this->documentManager->getObject());
		$this->assertNotContains($object, $this->documentManager->getObject());

		$repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
		$this->assertArrayNotHasKey($oid, $repositories);
		$this->assertEmpty($repositories);
	}

	public function test_addObject_removeObject(){
		$object = new \stdClass();
		$this->documentManager->addObject($object, DocumentManager::OBJ_NEW, "repository");

		$oid = spl_object_hash($object);
		$this->assertArrayHasKey($oid, $this->documentManager->getObject());
		$this->assertContains($object, $this->documentManager->getObject());

		$repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
		$this->assertArrayHasKey($oid, $repositories);
		$this->assertEquals("repository", $repositories[$oid]);

		$this->documentManager->removeObject($object);

		$this->assertArrayNotHasKey($oid, $this->documentManager->getObject());
		$this->assertNotContains($object, $this->documentManager->getObject());

		$repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
		$this->assertArrayNotHasKey($oid, $repositories);
		$this->assertEmpty($repositories);
	}

	public function test_delete_clear(){
		$object = new \stdClass();
		
		$this->documentManager->addObject($object, DocumentManager::OBJ_MANAGED, "repository");

		$oid = spl_object_hash($object);
		$this->assertArrayHasKey($oid, $this->documentManager->getObject());
		$this->assertContains($object, $this->documentManager->getObject());

		$repositories = $this->getPropertyValue($this->documentManager, "objectsRepository");
		$this->assertArrayHasKey($oid, $repositories);
		$this->assertEquals("repository", $repositories[$oid]);

		$this->documentManager->remove($object);
		$this->assertArrayHasKey($oid, $this->documentManager->getObject(DocumentManager::OBJ_REMOVED));
		$this->assertContains($object, $this->documentManager->getObject(DocumentManager::OBJ_REMOVED));

		$this->documentManager->clear();
		$this->documentManager->addObject($object, DocumentManager::OBJ_NEW, "repository");
		$this->expectException(StateException::class);
		$this->documentManager->remove($object);
	}

	public function test_flush(){
		$rep = $this->createMock(Repository::class);

		$obj1 = new \stdClass();
		$this->documentManager->addObject($obj1, DocumentManager::OBJ_NEW, $rep);

		$obj2 = new \stdClass();
		$this->documentManager->addObject($obj2, DocumentManager::OBJ_MANAGED, $rep);

		$obj3 = new \stdClass();
		$this->documentManager->addObject($obj3, DocumentManager::OBJ_REMOVED, $rep);

		$rep->expects($this->once())
			->method("deleteOne")
			->with($obj3);
		$rep->expects($this->once())
			->method("updateOne")
			->with($obj2);
		$rep->expects($this->once())
			->method("insertMany")
			->with([$obj1]);

		$this->documentManager->flush();
	}
}