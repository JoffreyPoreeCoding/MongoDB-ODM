<?php

namespace JPC\Test\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class ClassMetadataFactoryTest extends TestCase
{

    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;

    public function setUp()
    {
        $this->classMetadataFactory = new ClassMetadataFactory();
    }

    public function testGetMetadataForClassInexisting()
    {
        $this->expectException(\Exception::class);

        $this->classMetadataFactory->getMetadataForClass("Inexisting");
    }

    public function testGetMetadataForClass()
    {
        $classMeta = $this->classMetadataFactory->getMetadataForClass("stdClass");

        $this->assertInstanceOf(\JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata::class, $classMeta);

        $this->assertCount(1, $this->getPropertyValue($this->classMetadataFactory, "loadedMetadatas"));
    }
}
