<?php

namespace JPC\Test\MongoDB\ODM\Factory;

use JPC\Test\MongoDB\ODM\Framework\TestCase;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;

class ClassMetadataFactoryTest extends TestCase {
    
    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;
    
    public function setUp(){
        $this->classMetadataFactory = new ClassMetadataFactory();
    }
    
    public function test_getMetadataForClass_inexisting(){
        $this->expectException(\Exception::class);
        
        $this->classMetadataFactory->getMetadataForClass("Inexisting");
    }
    
    public function test_getMetadataForClass(){
        $classMeta = $this->classMetadataFactory->getMetadataForClass("stdClass");
        
        $this->assertInstanceOf(\JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata::class, $classMeta);
        
        $this->assertCount(1, $this->getPropertyValue($this->classMetadataFactory, "loadedMetadatas"));
    }
}
