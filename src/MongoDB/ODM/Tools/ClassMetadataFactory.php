<?php

namespace JPC\MongoDB\ODM\Tools;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use axelitus\Patterns\Creational\Singleton;

/**
 *
 * @author JoffreyP
 */
class ClassMetadataFactory extends Singleton {

    /**
     * Salt For Cache
     * @var type 
     */
    private $cacheSalt = '$CLASSMETADATA';
    
    /**
     *
     * @var ApcuCache 
     */
    private $annotationsCache;
    
    /**
     *
     * @var CachedReader 
     */
    private $reader;

    /**
     * Class metadatas
     * @var array
     */
    private $loadedMetadatas = [];

    public function __construct() {
        $this->annotationsCache = new ApcuCache();
        $this->reader = new CachedReader(new IndexedReader(new AnnotationReader()), $this->annotationsCache, false);
    }
    
    /**
     * 
     * @param string $className
     * @return ClassMetadata
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
    
    private function loadMetadataForClass($className){
        $classMetadatas = new ClassMetadata($className, $this->reader);
        $this->annotationsCache->save($className.$this->cacheSalt, $classMetadatas);
        return $classMetadatas;
    }

}
