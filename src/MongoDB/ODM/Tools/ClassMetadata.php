<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM\Tools;

/**
 * Description of ClassMetadata
 *
 * @author JoffreyP
 */
class ClassMetadata {
    
    private $cacheSalt;

    private $className;
    
    /**
     *
     * @var \Doctrine\Common\Annotations\AnnotationReader 
     */
    private $reader;
    
    private $name;
    
    private $classAnnotations;
    
    private $propertiesAnnotations = [];
    
    function __construct($className, $reader) {
        $this->className = $className;
        $this->reader = $reader;
        
        $this->generateMetadatas();
    }
    
    private function generateMetadatas(){
        $reflectionClass = new \ReflectionClass($this->className);
        
        $this->name = ($reflectionClass->getName());
        
        $this->readClassAnnotations($reflectionClass);
        $this->readPropertiesAnnotations($reflectionClass);
    }
    
    private function readClassAnnotations($reflectionClass){
        $annotations = $this->reader->getClassAnnotations($reflectionClass);
        $this->classAnnotations = $annotations;
    }
    
    private function readPropertiesAnnotations($reflectionClass){
        foreach ($reflectionClass->getProperties() as $property) {
            $this->propertiesAnnotations[$property->name] = ["property" => $property, "annotations" => $this->reader->getPropertyAnnotations($property)];
        }
    }
    
    public function getProperty(){
        
    }

}
