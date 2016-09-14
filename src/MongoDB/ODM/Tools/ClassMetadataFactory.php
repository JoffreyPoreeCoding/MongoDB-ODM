<?php

namespace JPC\MongoDB\ODM\Tools;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 *
 * @author JoffreyP
 */
class ClassMetadataFactory {

    /**
     * Salt For Cache
     * @var type 
     */
    private $cacheSalt = '$CLASSMETADATA';
    
    /**
     *
     * @var ApcuCache 
     */
    private $cache;
    
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
        $this->cache = new ApcuCache();
        $this->reader = new CachedReader(new IndexedReader(new AnnotationReader()), $this->cache, false);
    }
    
    public function getMetadataForClass($className){
        if(isset($this->loadedMetadatas[$className])){
            return $this->loadedMetadatas[$className];
        }
        
        if($this->cache->fetch($className.$this->cacheSalt)){
            $this->loadedMetadatas[$className] = $this->cache->fetch($className.$this->cacheSalt);
            return $this->loadedMetadatas[$className];
        }
        
        $this->loadedMetadatas[$className] = $this->loadMetadataForClass($className);
    }
    
    private function loadMetadataForClass($className){
        $classMetadatas = new ClassMetadata($className, $this->reader);
        return $classMetadatas;
    }

}
