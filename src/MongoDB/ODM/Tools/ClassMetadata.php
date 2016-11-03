<?php

namespace JPC\MongoDB\ODM\Tools;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Allow to interact with class metadatas and annotations
 */
class ClassMetadata {
    /* ================================== */
    /*              CONSTANTS             */
    /* ================================== */

    const CLASS_ANNOT = '$CLASS';
    const PROPERTIES_ANNOT = '$PROPETIES';

    /* ================================== */
    /*             PROPERTIES             */
    /* ================================== */

    /**
     * Salt for caching
     * @var     string
     */
    private $cacheSalt = '$ANNOTATIONS';

    /**
     * Annotation Reader
     * @var     AnnotationReader 
     */
    private $reader;

    /**
     * Class name
     * @var     string
     */
    private $name;

    /**
     * Class annotations
     * @var     array
     */
    private $classAnnotations;

    /**
     * List of properties
     * @var     array
     */
    private $properties = [];

    /**
     * Properties annotations
     * @var     array 
     */
    private $propertiesAnnotations = [];

    /**
     * Cache
     * @var     ApcuCache 
     */
    private $annotationCache;

    /* ================================== */
    /*          PUBLICS FUNCTIONS         */
    /* ================================== */

    /**
     * Create new ClassMetadata
     * 
     * @param   string              $className          Name of the class
     * @param   AnnotationReader    $reader             Annotation reader
     */
    public function __construct($className, $reader) {
        $this->reader = $reader;

        $this->name = $className;
        $this->annotationCache = new ApcuCache();
    }

    /**
     * Allow to get the class name
     * 
     * @return  string              Class name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Check if class has annotation
     * 
     * @param   string              $annotationName     Annotation name (Class)
     * 
     * @return  boolean             True if yes, else false
     */
    public function hasClassAnnotation($annotationName) {

        //Check if annotation are already loaded, if not loaded it
        if (!isset($this->classAnnotations)) {
            $this->readClassAnnotations();
        }

        //If annotation exist return true
        if (isset($this->classAnnotations[$annotationName])) {
            return true;
        }
        return false;
    }

    /**
     * Allow to get the annotation object
     * 
     * @param   string              $annotationName     Annotation name (Class)
     * 
     * @return  mixed               Annotation
     */
    public function getClassAnnotation($annotationName) {
        //Check if annotation exist
        if (!$this->hasClassAnnotation($annotationName)) {
            return null;
        }

        return $this->classAnnotations[$annotationName];
    }

    /**
     * Allow to get properties of the class
     * 
     * @return  array               Properties of the class
     */
    public function getProperties() {
        //Return properties annotation
        return $this->readPropertiesAnnotations();
    }

    /**
     * Allow to get a single property
     * 
     * @param   string              $name               Property name
     * 
     * @return  \ReflectionProperty Property
     */
    public function getProperty($name) {
        if (!isset($this->properties[$name])) {
            $this->properties[$name] = (new \ReflectionClass($this->name))->getProperty($name);
        }
        return $this->properties[$name];
    }

    /**
     * Check if property has annotation
     * 
     * @param   string              $propertyName       Property name
     * @param   string              $annotationName     Annotation name (Class)
     * 
     * @return  boolean             True if yes, else false
     */
    public function hasPropertyAnnotation($propertyName, $annotationName) {
        if (!isset($this->propertiesAnnotations[$propertyName])) {
            $this->readPropertiesAnnotations();
        }

        if (isset($this->propertiesAnnotations[$propertyName][$annotationName])) {
            return true;
        }
        return false;
    }

    /**
     * Allow to get property annotation
     * 
     * @param   string              $propertyName       Property name
     * @param   string              $annotationName     Annotation name (Class)
     * 
     * @return  mixed               Annotation
     */
    public function getPropertyAnnotation($propertyName, $annotationName) {
        if (!$this->hasPropertyAnnotation($propertyName, $annotationName)) {
            return false;
        }

        return $this->propertiesAnnotations[$propertyName][$annotationName];
    }

    public function getPropertyWithAnnotation($annotationName) {
        if (empty($this->propertiesAnnotations)) {
            $this->readPropertiesAnnotations();
        }

        foreach ($this->propertiesAnnotations as $property => $annotations) {
            foreach ($annotations as $name => $value) {
                if ($name == $annotationName) {
                    return [$property => $value];
                }
            }
        }
    }

    /* ================================== */
    /*          PRIVATES FUNCTIONS        */
    /* ================================== */

    /**
     * Read class annotations (From loaded, then cache, then file)
     * 
     * @return  array               All class's annotations
     */
    private function readClassAnnotations() {
        if (isset($this->classAnnotations)) {
            return $this->classAnnotations;
        }

        if ($this->annotationCache->contains($this->name . self::CLASS_ANNOT . $this->cacheSalt)) {
            $this->classAnnotations = $this->annotationCache->fetch($this->name . self::CLASS_ANNOT . $this->cacheSalt);
            return $this->classAnnotations;
        }

        return $this->doReadClassAnnotations();
    }

    /**
     * Read class annotation from file
     * 
     * @return array               All class's annotations
     */
    private function doReadClassAnnotations() {
        $annotations = $this->reader->getClassAnnotations(new \ReflectionClass($this->name));

        foreach ($annotations as $annot) {
            $class = get_class($annot);
            if (!isset($this->classAnnotations[$class])) {
                $this->classAnnotations[$class] = $annot;
            } else if (is_array($this->classAnnotations[$class])) {
                $this->classAnnotations[$class][] = $annot;
            } else {
                $this->classAnnotations[$class] = [$this->classAnnotations[$class], $annot];
            }
        }

        $this->annotationCache->save($this->name . self::CLASS_ANNOT . $this->cacheSalt, $annotations);
        return $this->classAnnotations;
    }

    /**
     * Read all properties's annotations (From loaded, then cache, then file)
     * 
     * @return array               All properties's annotations
     */
    private function readPropertiesAnnotations() {
        if (isset($this->propertiesAnnotations) && !empty($this->propertiesAnnotations)) {
            return $this->propertiesAnnotations;
        }

        if ($this->annotationCache->contains($this->name . self::PROPERTIES_ANNOT . $this->cacheSalt)) {
            $this->propertiesAnnotations = $this->annotationCache->fetch($this->name . self::PROPERTIES_ANNOT . $this->cacheSalt);
            return $this->propertiesAnnotations;
        }

        return $this->doReadPropertiesAnnotations();
    }

    /**
     * Read all properties's annotations from file
     * 
     * @return array               All class's annotations
     */
    private function doReadPropertiesAnnotations() {
        foreach ((new \ReflectionClass($this->name))->getProperties() as $property) {
            $this->properties[$property->name] = $property;
            $propAnnot = [];
            foreach ($this->reader->getPropertyAnnotations($property) as $annot) {
                $class = get_class($annot);
                if (!isset($propAnnot[$class])) {
                    $propAnnot[$class] = $annot;
                } else if (is_array($propAnnot[$class])) {
                    $propAnnot[$class][] = $annot;
                } else {
                    $propAnnot[$class] = [$propAnnot[$class], $annot];
                }
            }
            $this->propertiesAnnotations[$property->name] = $propAnnot;
        }

        $this->annotationCache->save($this->name . self::PROPERTIES_ANNOT . $this->cacheSalt, $this->propertiesAnnotations);
        return $this->propertiesAnnotations;
    }

}
