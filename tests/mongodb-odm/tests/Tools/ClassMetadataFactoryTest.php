<?php

use JPC\MongoDB\ODM\Tools\ClassMetadataFactory;

require_once __DIR__."/../../models/SimpleDocument.php";

class ClassMetadataFactoryTest extends PHPUnit_Framework_TestCase {

    /**
     * Reflection Class
     * @var \ReflectionClass
     */
    private $reflectionClass;
    
    /**
     * Class Metadata Factory
     * @var ClassMetadataFactory 
     */
    private $classMetadataFactory;

    public function __construct() {
        $this->reflectionClass = new ReflectionClass("JPC\MongoDB\ODM\Tools\ClassMetadataFactory");
        $this->classMetadataFactory = new ClassMetadataFactory();
    }

    public function testGetMetadataForClass() {
        $metadata = $this->classMetadataFactory->getMetadataForClass("SimpleDocument");
        
        $this->assertInstanceOf("JPC\MongoDB\ODM\Tools\ClassMetadata", $metadata);
        
        $loaded = $this->reflectionClass->getProperty("loadedMetadatas");
        $loaded->setAccessible(true);
        
        $this->assertCount(1, $loaded->getValue($this->classMetadataFactory));
    }

}
