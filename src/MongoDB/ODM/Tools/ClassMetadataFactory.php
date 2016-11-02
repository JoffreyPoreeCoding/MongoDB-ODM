<?php

namespace JPC\MongoDB\ODM\Tools;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use JPC\MongoDB\ODM\Tools\ClassMetadata;

/**
 * Allow to get Class metadatas
 */
class ClassMetadataFactory {
    
    use \JPC\DesignPattern\Singleton;
    
    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

    /**
     * Salt For Cache
     * @var string 
     */
    private $cacheSalt = '$CLASSMETADATA';
    
    /**
     * Cache
     * @var ApcuCache 
     */
    private $annotationsCache;
    
    /**
     * Annotation Reader
     * @var CachedReader 
     */
    private $reader;

    /**
     * Class metadatas
     * @var array
     */
    private $loadedMetadatas = [];

    /* ================================== */
    /*           PUBLICS FUNCTIONS        */
    /* ================================== */
    
    /**
     * Create new Class metadata factory
     */
    public function __construct() {
        $this->annotationsCache = new ApcuCache();
        $this->reader = new CachedReader(new AnnotationReader(), $this->annotationsCache, false);
    }
    
    /**
     * Allow to get class metadata for specified class
     * 
     * @param   string          $className          Name of the class to get metadatas
     * 
     * @return  ClassMetadata   Class metadatas
     */
    public function getMetadataForClass($className){
        if(isset($this->loadedMetadatas[$className])){
            return $this->loadedMetadatas[$className];
        }
        
        if($this->annotationsCache->fetch($className.$this->cacheSalt)){
            $this->loadedMetadatas[$className] = $this->annotationsCache->fetch($className.$this->cacheSalt);
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
        $classMetadatas = new ClassMetadata($className, $this->reader);
        $this->annotationsCache->save($className.$this->cacheSalt, $classMetadatas);
        return $classMetadatas;
    }

}
