<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\Test\MongoDB\ODM\TestCase;
use JPC\MongoDB\ODM\Tools\ClassMetadataFactory;

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
        $classMeta = $this->classMetadataFactory->getMetadataForClass("JPC\Test\MongoDB\ODM\Model\ObjectMapping");
        
        $this->assertInstanceOf(\JPC\MongoDB\ODM\Tools\ClassMetadata::class, $classMeta);
        
        $this->assertCount(1, $this->getPropertyValue($this->classMetadataFactory, "loadedMetadatas"));
    }
}
