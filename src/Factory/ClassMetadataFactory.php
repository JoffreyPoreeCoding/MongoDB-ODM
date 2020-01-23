<?php

namespace JPC\MongoDB\ODM\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

/**
 * Allow to get Class metadatas
 */
class ClassMetadataFactory
{

    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

    /**
     * Classes metadata
     * @var ArrayCache
     */
    private $loadedMetadata;

    /**
     * Annotation Reader for classes metadata
     * @var AnnotationReader
     */
    private $annotationReader;

    /* ================================== */
    /*             CONSTRUCTOR            */
    /* ================================== */

    /**
     * Create new class metadata factory
     *
     * @param AnnotationReader|null     $annotationReader   Annotation reader that will be used in class metadatas
     */
    public function __construct(AnnotationReader $annotationReader = null, Cache $loadedMetadataCache = null)
    {
        $this->annotationReader = isset($annotationReader) ? $annotationReader : new CachedReader(new AnnotationReader(), new ApcuCache(), false);
        $this->loadedMetadata = $loadedMetadataCache ?? new ArrayCache();
    }

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
    /**
     * Allow to get class metadata for specified class
     *
     * @param   string          $className          Name of the class to get metadatas
     *
     * @return  ClassMetadata   Class metadatas
     */
    public function getMetadataForClass($className)
    {
        if (!class_exists($className)) {
            throw new \Exception("Class $className does not exist!");
        }
        if (false == ($classMetadata = $this->loadedMetadata->fetch($className))) {
            $classMetadata = $this->loadMetadataForClass($className, $this->annotationReader);
        }

        $this->loadedMetadata->save($className, $classMetadata, 10);

        return $classMetadata;
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
    private function loadMetadataForClass($className)
    {
        $classMetadatas = new ClassMetadata($className, $this->annotationReader);
        return $classMetadatas;
    }
}
