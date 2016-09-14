<?php

namespace JPC\MongoDB\ODM;

use axelitus\Patterns\Creational\Multiton;

/**
 * Description of Hydrator
 *
 * @author poree
 */
class Hydrator extends Multiton {

    /**
     * Annotation Reader
     * @var \Doctrine\Common\Annotations\CachedReader
     */
    private $reader;

    /**
     * Class reflection
     * @var \ReflectionClass 
     */
    private $reflectionClass;

    /**
     * Reflection classes of embedded documents
     * @var array
     */
    private $embeddedReflectionClasses = [];

    function __construct(\Doctrine\Common\Annotations\CachedReader $reader, \ReflectionClass $reflectionClass) {
        $this->reader = $reader;
        $this->reflectionClass = $reflectionClass;
    }

    function hydrate($object, $datas) {
        $properties = $this->reflectionClass->getProperties();

        foreach ($properties as $property) {
            $fieldName = $value = null;
            $annotations = $this->reader->getPropertyAnnotations($property);
            if (isset($annotations["JPC\MongoDB\ODM\Annotations\Mapping\Field"])) {
                $fieldName = $annotations["JPC\MongoDB\ODM\Annotations\Mapping\Field"]->name;

                if (isset($datas[$annotations["JPC\MongoDB\ODM\Annotations\Mapping\Field"]->name])) {
                    $value = $datas[$annotations["JPC\MongoDB\ODM\Annotations\Mapping\Field"]->name];
                }

                if (isset($value) && is_object($value) && isset($annotations["JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument"])) {
                    $value = $this->convertEmbedded($value, $annotations["JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument"]->document);
                }
            }

            if (isset($fieldName) && isset($value)) {
                $property->setAccessible(true);
                $property->setValue($object, $value);
            }
        }
    }

    public function unhydrate($object) {
        $datas = [];
        $properties = $this->reflectionClass->getProperties();

        foreach ($properties as $property) {
            $annotations = $this->reader->getPropertyAnnotations($property);
            if (isset($annotations["JPC\MongoDB\ODM\Annotations\Mapping\Field"])) {
                $fieldName = $annotations["JPC\MongoDB\ODM\Annotations\Mapping\Field"]->name;
                $property->setAccessible(true);
                $value = $property->getValue($object);

                if (isset($value) && is_object($value) && isset($annotations["JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument"])) {
                    $hydrator = Hydrator::instance($annotations["JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument"]->document);
                    $value = $hydrator->unhydrate($value);
                }
            }

            if (isset($fieldName) && isset($value)) {
                $datas[$fieldName] = $value;
            }
        }

        return $datas;
    }

    private function convertEmbedded($value, $embeddedDocument) {
        $object = new $embeddedDocument();

        if (!isset($this->embeddedReflectionClasses[$embeddedDocument])) {
            $this->embeddedReflectionClasses[$embeddedDocument] = new \ReflectionClass($embeddedDocument);
        }

        $hydrator = Hydrator::instance("$embeddedDocument", $this->reader, $this->embeddedReflectionClasses[$embeddedDocument]);

        $hydrator->hydrate($object, $value);

        return $object;
    }

}
