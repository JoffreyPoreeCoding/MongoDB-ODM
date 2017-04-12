<?php 

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use JPC\Test\MongoDB\ODM\Model\ObjectMapping;
use MongoDB\Collection;

class HydratorTest extends TestCase {

	private $repositoryFactory;

	private $hydrator;

	public function setUp(){
		$classMetadata = new ClassMetadata("JPC\Test\MongoDB\ODM\Model\ObjectMapping");
		$classMetadataFactory = new ClassMetadataFactory();
		$documentManager = $this->createMock(DocumentManager::class);
		$this->repositoryFactory = $this->createMock(RepositoryFactory::class);

		$this->hydrator = new Hydrator($classMetadataFactory, $classMetadata, $documentManager, $this->repositoryFactory);
	}

	public function test_hydrate(){
		$repository = $this->createMock(Repository::class);
		$collection = $this->createMock(Collection::class);
		$hydrator = $this->createMock(Hydrator::class);
		$repository->method("getCollection")->willReturn($collection);
		$repository->method("getHydrator")->willReturn($hydrator);
		$collection->method("findOne")->willReturn(["_id" => "id"]);
		$collection->method("find")->willReturn([["_id" => "id"]]);
		$hydrator->expects($this->exactly(2))->method("hydrate")->will($this->returnCallback([$this, "fakeHydration"]));
		$this->repositoryFactory->method("getRepository")->willReturn($repository);

		$data = [
		"simple_field"         => "value 1",
		"embedded_field"       => [
		"simple_field"         => "value 2"
		],
		"multi_embedded_field" => [
		0                      => [
		"simple_field"         => "value 3"
		],
		1                      => [
		"simple_field"         => "value 4"
		],
		2                      => [
		"simple_field"         => "value 5"
		]
		],
		"refers_one_field" => "id",
		"refers_many_field" => ["id"]
		];

		$object = new ObjectMapping();
		$this->hydrator->hydrate($object, $data);

		$this->assertInstanceOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $object);
		$this->assertEquals("value 1", $object->getSimpleField());

		$this->assertInstanceOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $object->getEmbeddedField());
		$this->assertEquals("value 2", $object->getEmbeddedField()->getSimpleField());

		foreach($object->getMultiEmbeddedField() as $key => $embedded){
			$this->assertInstanceOf("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $embedded);
			$this->assertEquals("value " . ($key + 3), $embedded->getSimpleField());
		}

		$this->assertEquals("reference", $object->getRefersOneField()->getId());
		$this->assertEquals("reference", $object->getRefersManyField()[0]->getId());
	}

	public function fakeHydration($object){
		$object->setId("reference");
	}

	public function test_unhydrate(){
		$reference = new ObjectMapping();
		$reference->setId("reference");

		$object = new ObjectMapping();
		$object
		->setSimpleField("value 1")
		->setEmbeddedField((new ObjectMapping())->setSimpleField("value 2"))
		->setMultiEmbeddedField([
			(new ObjectMapping())->setSimpleField("value 3"),
			(new ObjectMapping())->setSimpleField("value 4"),
			(new ObjectMapping())->setSimpleField("value 5"),
			])
		->setRefersOneField($reference)
		->setRefersManyField([$reference]);
		;

		$unhydrated = $this->hydrator->unhydrate($object);

		$expected = [
		"simple_field"         => "value 1",
		"embedded_field"       => [
		"simple_field"         => "value 2",
		],
		"multi_embedded_field" => [
		0                      => [
		"simple_field"         => "value 3",
		],
		1                      => [
		"simple_field"         => "value 4",
		],
		2                      => [
		"simple_field"         => "value 5",
		]
		],
		"refers_one_field" => "reference",
		"refers_many_field" => ["reference"]
		];

		$this->assertEquals($expected, $unhydrated);
	}
}