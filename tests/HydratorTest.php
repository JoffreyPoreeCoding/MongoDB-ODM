<?php 

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Tools\ClassMetadata;
use JPC\Test\MongoDB\ODM\Model\ObjectMapping;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase {

	private $hydrator;

	public function setUp(){
		$classMetadata = new ClassMetadata("JPC\Test\MongoDB\ODM\Model\ObjectMapping");
		$this->hydrator = new Hydrator($this->createMock(DocumentManager::class), $classMetadata);
	}

	public function test_hydrate(){
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
		]
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
	}

	public function test_unhydrate(){
		$object = new ObjectMapping();
		$object
		->setSimpleField("value 1")
		->setEmbeddedField((new ObjectMapping())->setSimpleField("value 2"))
		->setMultiEmbeddedField([
			(new ObjectMapping())->setSimpleField("value 3"),
			(new ObjectMapping())->setSimpleField("value 4"),
			(new ObjectMapping())->setSimpleField("value 5"),
			])
		;

		$unhydrated = $this->hydrator->unhydrate($object);

		$expected = [
		"_id" => null,
		"simple_field"         => "value 1",
		"embedded_field"       => [
		"_id" => null,
		"simple_field"         => "value 2",
		"embedded_field" => null,
		"multi_embedded_field" => null
		],
		"multi_embedded_field" => [
		0                      => [
		"_id" => null,
		"simple_field"         => "value 3",
		"embedded_field" => null,
		"multi_embedded_field" => null
		],
		1                      => [
		"_id" => null,
		"simple_field"         => "value 4",
		"embedded_field" => null,
		"multi_embedded_field" => null
		],
		2                      => [
		"_id" => null,
		"simple_field"         => "value 5",
		"embedded_field" => null,
		"multi_embedded_field" => null
		]
		]
		];

		$this->assertEquals($expected, $unhydrated);
	}
}
