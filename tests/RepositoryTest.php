<?php

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata;
use MongoDB\Collection;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase {

	private $repository;

	private $documentManager;

	private $objectManager;

	private $classMetadata;

	private $collection;

	private $hydrator;

	public function setUp(){
		$this->documentManager = $this->createMock(DocumentManager::class);
		$this->objectManager = $this->createMock(ObjectManager::class);
		$this->classMetadata = $this->createMock(ClassMetadata::class);
		$this->classMetadata->method("getName")->willReturn("JPC\\Test\\MongoDB\\ODM\\Model\\ObjectMapping");
		$this->collection = $this->createMock(Collection::class);

		

		$this->repository = new Repository(
			$this->documentManager,
			$this->objectManager,
			$this->classMetadata,
			$this->collection
			);

		$this->hydrator = $this->createMock(Hydrator::class);
		$this->setPrivatePropertyValue($this->repository, "hydrator", $this->hydrator);
	}

	public function test_getCollection(){
		$this->assertInstanceOf("MongoDB\Collection", $this->repository->getCollection());
		$this->assertEquals($this->collection, $this->repository->getCollection());
	}

	public function test_getHydrator(){
		$this->assertInstanceOf("JPC\MongoDB\ODM\Hydrator", $this->repository->getHydrator());
		$this->assertEquals($this->hydrator, $this->repository->getHydrator());
	}

	public function test_count(){
		$this->collection->method("count")->willReturn(10);
		$this->assertEquals(10, $this->repository->count());
	}

	public function test_find(){
		$this->collection->method("findOne")->willReturn(null);
		$this->assertNull($this->repository->find("id"));

		$this->setUp();

		$this->collection->method("findOne")->willReturn(1);
		$this->hydrator->expects($this->once())->method("hydrate");
		$this->documentManager->expects($this->once())->method("persist");
		$this->objectManager->expects($this->once())->method("setObjectState");
		$this->assertInstanceOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $this->repository->find("id"));
	}

	public function test_findAll(){
		$this->collection->method("find")->willReturn([]);
		$this->assertEmpty($this->repository->findAll());

		$this->setUp();

		$this->collection->method("find")->willReturn([1, 2]);
		$this->hydrator->expects($this->exactly(2))->method("hydrate");
		$this->documentManager->expects($this->exactly(2))->method("persist");
		$this->objectManager->expects($this->exactly(2))->method("setObjectState");
		$result = $this->repository->findAll();
		$this->assertCount(2, $result);
		$this->assertContainsOnlyInstancesOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $result);
	}

	public function test_findBy(){
		$this->collection->method("find")->willReturn([]);
		$this->assertEmpty($this->repository->findBy([]));

		$this->setUp();

		$this->collection->method("find")->willReturn([1, 2]);
		$this->hydrator->expects($this->exactly(2))->method("hydrate");
		$this->documentManager->expects($this->exactly(2))->method("persist");
		$this->objectManager->expects($this->exactly(2))->method("setObjectState");
		$result = $this->repository->findBy([]);
		$this->assertCount(2, $result);
		$this->assertContainsOnlyInstancesOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $result);
	}

	public function test_findOneBy(){
		$this->collection->method("findOne")->willReturn(null);
		$this->assertNull($this->repository->findOneBy([]));

		$this->setUp();

		$this->collection->method("findOne")->willReturn(1);
		$this->hydrator->expects($this->once())->method("hydrate");
		$this->documentManager->expects($this->once())->method("persist");
		$this->objectManager->expects($this->once())->method("setObjectState");
		$this->assertInstanceOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $this->repository->findOneBy([]));
	}

	public function test_findAndModifyOneBy(){
		$this->collection->method("findOneAndUpdate")->willReturn(null);
		$this->assertNull($this->repository->findAndModifyOneBy([]));

		$this->setUp();

		$this->collection->method("findOneAndUpdate")->willReturn(1);
		$this->hydrator->expects($this->once())->method("hydrate");
		$this->documentManager->expects($this->once())->method("persist");
		$this->objectManager->expects($this->once())->method("setObjectState");
		$this->assertInstanceOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $this->repository->findAndModifyOneBy([]));
	}

	private function setPrivatePropertyValue($object, $propName, $value){
		$prop = new \ReflectionProperty($object, $propName);
		$prop->setAccessible(true);
		$prop->setValue($object, $value);
	}


}

