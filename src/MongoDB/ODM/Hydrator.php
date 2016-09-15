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
     * @var Tools\ClassMetadata
     */
    private $classMetadata;

    /**
     * Reflection classes of embedded documents
     * @var array
     */
    private $embeddedReflectionClasses = [];
    private $propertiesInfos = [];

    function __construct($classMetadata) {
        $this->classMetadata = $classMetadata;
    }

    function hydrate($object, $datas) {
        $propertiesAnnotations = $this->classMetadata->getProperties();
        
        foreach ($propertiesAnnotations as $name => $propertyAnnotations) {
            $value = null;
            $fieldInfos = $this->getFieldInfos($name);
            if (isset($fieldInfos["field"]) && isset($datas[$fieldInfos["field"]])) {
                if (isset($fieldInfos["embedded"])) {
                    $datas[$fieldInfos["field"]] = $this->convertEmbedded($datas[$fieldInfos["field"]], $fieldInfos["embedded"]);
                }

                $prop = $this->classMetadata->getProperty($name);
                $prop->setAccessible(true);
                $prop->setValue($object, $datas[$fieldInfos["field"]]);
            }
        }
    }

    public function unhydrate($object) {
        $datas = [];
        $properties = $this->classMetadata->getProperties();

        foreach ($properties as $name => $property) {
            if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")) {
                $property["property"]->setAccessible(true);
                $value = $property["property"]->getValue($object);

                if (isset($value) && is_object($value) && $this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")) {
                    $hydrator = Hydrator::instance($this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")->document);
                    $value = $hydrator->unhydrate($value);
                }
            }

            if (isset($value)) {
                $datas[$this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")->name] = $value;
            }
        }

        return $datas;
    }

    private function getFieldInfos($name) {
        if (!isset($this->propertiesInfos[$name])) {
            $this->propertiesInfos[$name] = [];
            if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")) {
                $this->propertiesInfos[$name]["field"] = $this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")->name;
                if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")) {
                    $this->propertiesInfos[$name]["embedded"] = $this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")->document;
                }
            }
        }

        return $this->propertiesInfos[$name];
    }

    private function convertEmbedded($value, $embeddedDocument) {
        $object = new $embeddedDocument();

        if (!isset($this->embeddedReflectionClasses[$embeddedDocument])) {
            $this->embeddedReflectionClasses[$embeddedDocument] = new \ReflectionClass($embeddedDocument);
        }

        $hydrator = Hydrator::instance($embeddedDocument, Tools\ClassMetadataFactory::instance()->getMetadataForClass($embeddedDocument));

        $hydrator->hydrate($object, $value);

        return $object;
    }

}
