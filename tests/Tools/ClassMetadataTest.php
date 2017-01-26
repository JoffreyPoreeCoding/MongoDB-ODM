<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\Test\MongoDB\ODM\TestCase;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class ClassMetadataTest extends TestCase {
    
    /**
     * @var ClassMetadata
     */
    private $classMetadata;
    
    public function setUp(){
        $this->classMetadata = new ClassMetadata(\JPC\Test\MongoDB\ODM\Model\ObjectMapping::class);
    }
    
    public function test_getName(){
        $this->assertEquals("JPC\Test\MongoDB\ODM\Model\ObjectMapping", $this->classMetadata->getName());
    }
    
    public function test_getColection(){
        $this->assertEquals("object_mapping", $this->classMetadata->getCollection());
    }
    
    public function test_getPropertiesInfos(){
        $this->assertContainsOnlyInstancesOf("JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo", $this->classMetadata->getPropertiesInfos());
    }
    
    public function test_getPropertyForField(){
        $this->assertInstanceOf("ReflectionProperty", $this->classMetadata->getPropertyForField("simple_field"));
        $this->assertFalse($this->classMetadata->getPropertyForField("inexisting"));
    }
    
    public function test_getPropertyInfoForField(){
        $this->assertInstanceOf("JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo", $this->classMetadata->getPropertyInfo("simpleField"));
        $this->assertFalse($this->classMetadata->getPropertyInfo("inexisting"));
    }
    
    public function test_getPropertyInfo(){
        $this->assertInstanceOf("JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo", $this->classMetadata->getPropertyInfoForField("simple_field"));
        $this->assertFalse($this->classMetadata->getPropertyInfoForField("inexisting"));
    }
    
    public function test_getRepositoryClass(){
        $this->assertEquals("JPC\MongoDB\ODM\Repository", $this->classMetadata->getRepositoryClass());
    }
    
    public function test_getCollectionOptions(){
        $this->assertEmpty($this->classMetadata->getCollectionOptions());
    }
    
    public function test_getCollectionCreationOptions(){
        $this->assertEmpty($this->classMetadata->getCollectionCreationOptions());
    }
    
}
