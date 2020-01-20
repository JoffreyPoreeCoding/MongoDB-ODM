<?php

namespace JPC\MongoDB\ODM\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
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
     * Class metadatas
     * @var array
     */
    private $loadedMetadatas = [];

    /**
     * Annotation Reader for class metadatas
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
    public function __construct(AnnotationReader $annotationReader = null)
    {
        $this->annotationReader = isset($annotationReader) ? $annotationReader : new CachedReader(new AnnotationReader(), new ApcuCache(), false);
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
    public function getMetadataForClass($className)
    {
        if (!class_exists($className)) {
            throw new \Exception("Class $className does not exist!");
        }
        if (isset($this->loadedMetadatas[$className])) {
            return $this->loadedMetadatas[$className];
        }

        return $this->loadedMetadatas[$className] = $this->loadMetadataForClass($className, $this->annotationReader);
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
