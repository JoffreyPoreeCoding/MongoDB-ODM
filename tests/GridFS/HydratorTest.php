<?php

namespace JPC\Test\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\GridFS\Hydrator;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\Test\MongoDB\ODM\GridFS\Model\GridFSObjectMapping;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase {

	private $hydrator;

	public function setUp(){
		$classMetadata = new ClassMetadata(GridFSObjectMapping::class);
		$classMetadataFactory = new ClassMetadataFactory();
		
		$this->hydrator = new Hydrator($classMetadataFactory, $classMetadata);
	}

	public function test_hydrate(){
		$datas = [
		"_id" => "id",
		"filename" => "filename",
		"length" => 1024,
		"contentType" => "plain/text",
		"md5" => "abcdef",
		"simple_metadata" => "metadata"
		];

		$object = new GridFSObjectMapping();

		$this->hydrator->hydrate($object, $datas);
		
		$this->assertEquals("id", $object->getId());
		$this->assertEquals("filename", $object->getFilename());
		$this->assertEquals(1024, $object->getLength());
		$this->assertEquals("plain/text", $object->getContentType());
		$this->assertEquals("abcdef", $object->getMd5());
		$this->assertEquals("metadata", $object->getSimpleMetadata());
	}

	public function test_unhydrate(){
		$object = new GridFSObjectMapping();
		$object
		->setId("id")
		->setFilename("filename")
		->setContentType("plain/text")
		->setSimpleMetadata("metadata")
		;

		$expected = [
		"_id" => "id",
		"filename" => "filename",
		"contentType" => "plain/text",
		"metadata" => [
		"simple_metadata" => "metadata"
		]
		];

		$this->assertEquals($expected, $this->hydrator->unhydrate($object));
	}
}