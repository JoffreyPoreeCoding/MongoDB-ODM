<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Allow to get Class metadatas
 */
class ClassMetadataFactory {
    
    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

    /**
     * Class metadatas
     * @var array
     */
    private $loadedMetadatas = [];

    /* ================================== */
    /*           PUBLICS FUNCTIONS        */
    /* ================================== */
    
    /**
     * Allow to get class metadata for specified class
     * 
     * @param   string          $className          Name of the class to get metadatas
     * 
     * @return  ClassMetadata   Class metadatas
     */
    public function getMetadataForClass($className){
        if(!class_exists($className)){
            throw new \Exception("Class $className does not exist!");
        }
        if(isset($this->loadedMetadatas[$className])){
            return $this->loadedMetadatas[$className];
        }
        
        return $this->loadedMetadatas[$className] = $this->loadMetadataForClass($className);
    }
    
    /* ================================== */
    /*          PRIVATES FUNCTIONS        */
    /* ================================== */
    
    /**
     * Load class metadatas
     * 
     * @param   string          $className Name of the class to get metadatas
     * 
     * @return  ClassMetadata   Class metadatas
     */
    private function loadMetadataForClass($className){
        $classMetadatas = new ClassMetadata($className);
        return $classMetadatas;
    }

}
