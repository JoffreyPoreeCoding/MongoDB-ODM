<?php

use JPC\MongoDB\ODM\Tools\ClassMetadata;
use Doctrine\Common\Annotations\AnnotationReader;

require_once __DIR__."/../../models/SimpleDocument.php";

class ClassMetadataTest extends PHPUnit_Framework_TestCase {

    /**
     * Reflection Class
     * @var \ReflectionClass
     */
    private $reflectionClass;
    
    /**
     * Class Metadata Factory
     * @var ClassMetadata
     */
    private $classMetadata;

    public function __construct() {
        $this->reflectionClass = new ReflectionClass("JPC\MongoDB\ODM\Tools\ClassMetadata");
        $this->classMetadata = new ClassMetadata("SimpleDocument", new Doctrine\Common\Annotations\IndexedReader(new AnnotationReader()));
    }

    public function testGetName() {
        $this->assertEquals("SimpleDocument", $this->classMetadata->getName());
    }
    
    public function testHasClassAnnotation(){
        $result = $this->classMetadata->hasClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document");
        
        $this->assertTrue($result);
        
        $prop = $this->reflectionClass->getProperty("classAnnotations");
        $prop->setAccessible(true);
        $this->assertNotEmpty($prop->getValue($this->classMetadata));
    }
    
    public function testGetClassAnnotation(){
        $result = $this->classMetadata->getClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document");
        
        $this->assertInstanceOf("JPC\MongoDB\ODM\Annotations\Mapping\Document", $result);
        $this->assertEquals("simple_doc", $result->collectionName);
        $this->assertNull($result->repositoryClass);
    }

}
