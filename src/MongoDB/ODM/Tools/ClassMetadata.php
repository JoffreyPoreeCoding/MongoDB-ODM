<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM\Tools;

use Doctrine\Common\Cache\ApcuCache;

/**
 * Description of ClassMetadata
 *
 * @author JoffreyP
 */
class ClassMetadata {

    const CLASS_ANNOT = '$CLASS';
    const PROPERTIES_ANNOT = '$PROPETIES';

    private $cacheSalt = '$ANNOTATIONS';
    private $className;

    /**
     *
     * @var \Doctrine\Common\Annotations\AnnotationReader 
     */
    private $reader;
    
    /**
     * Class name
     * @var string
     */
    private $name;
    
    /**
     * Class annotations
     * @var array
     */
    private $classAnnotations;
    
    /**
     * List of properties
     * @var array
     */
    private $properties = [];
    
    /**
     * Properties annotations
     * @var array 
     */
    private $propertiesAnnotations = [];
    
    /**
     * Relection class
     * @var \ReflectionClass 
     */
    private $reflectionClass;
    
    /**
     *
     * @var ApcuCache 
     */
    private $annotationCache;

    public function __construct($className, $reader) {
        $this->className = $className;
        $this->reader = $reader;

        $this->name = ($className);
        $this->annotationCache = new ApcuCache();
    }

    public function getName(){
        return $this->name;
    }

    public function hasClassAnnotation($annotationName) {
        if (!isset($this->classAnnotations)) {
            $this->readClassAnnotations();
        }

        if (isset($this->classAnnotations[$annotationName])) {
            return true;
        }
        return false;
    }
    
    public function getClassAnnotation($annotationName){
        if(!$this->hasClassAnnotation($annotationName)){
            return null;
        }
        
        return $this->classAnnotations[$annotationName];
    }

    public function getProperties() {
        return $this->readPropertiesAnnotations();
    }
    
    public function getProperty($name){
        if(!isset($this->properties[$name])){
            $this->properties[$name] = (new \ReflectionClass($this->className))->getProperty($name);
        }
        return $this->properties[$name];
    }
    
    public function hasPropertyAnnotation($propertyName, $annotationName){
        if (!isset($this->propertiesAnnotations)) {
            $this->readPropertiesAnnotations();
        }

        if (isset($this->propertiesAnnotations[$propertyName][$annotationName])) {
            return true;
        }
        return false;
    }
    
    public function getPropertyAnnotation($propertyName, $annotationName){
        if(!$this->hasPropertyAnnotation($propertyName, $annotationName)){
            return null;
        }
        
        return $this->propertiesAnnotations[$propertyName][$annotationName];
    }
    
    private function readClassAnnotations() {
        if (isset($this->classAnnotations)) {
            return $this->classAnnotations;
        }
        
        if($this->annotationCache->contains($this->name.self::CLASS_ANNOT.$this->cacheSalt)){
            $this->classAnnotations = $this->annotationCache->fetch($this->name.self::CLASS_ANNOT.$this->cacheSalt);
            return $this->classAnnotations;
        }

        return $this->doReadClassAnnotations();
    }

    private function doReadClassAnnotations() {
        $annotations = $this->reader->getClassAnnotations(new \ReflectionClass($this->className));
        $this->classAnnotations = $annotations;
        $this->annotationCache->save($this->name.self::CLASS_ANNOT.$this->cacheSalt, $annotations);
        return $this->classAnnotations;
    }

    private function readPropertiesAnnotations() {
        if (isset($this->propertiesAnnotations) && !empty($this->propertiesAnnotations)) {
            return $this->propertiesAnnotations;
        }
        
        if($this->annotationCache->contains($this->name.self::PROPERTIES_ANNOT.$this->cacheSalt)){
            $this->propertiesAnnotations = $this->annotationCache->fetch($this->name.self::PROPERTIES_ANNOT.$this->cacheSalt);
            return $this->propertiesAnnotations;
        }

        return $this->doReadPropertiesAnnotations();
    }

    private function doReadPropertiesAnnotations() {
        foreach ((new \ReflectionClass($this->className))->getProperties() as $property) {
            $this->properties[$property->name] = $property;
            $this->propertiesAnnotations[$property->name] = $this->reader->getPropertyAnnotations($property);
        }
        
        $this->annotationCache->save($this->name.self::PROPERTIES_ANNOT.$this->cacheSalt, $this->propertiesAnnotations);
        return $this->propertiesAnnotations;
    }
}
