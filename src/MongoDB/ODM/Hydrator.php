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

    /**
     * Properties infos
     * @var array
     */
    private $propertiesInfos = [];

    /**
     * Field mapping with mongo field as key and property name as value
     * @var array
     */
    private $fieldMapping = [];
    
    function __construct($classMetadata) {
        $this->classMetadata = $classMetadata;
    }

    function hydrate(&$object, $datas) {
        $propertiesAnnotations = $this->classMetadata->getProperties();

        foreach ($propertiesAnnotations as $name => $propertyAnnotations) {
            if(!($modifiers = DocumentManager::instance()->getModifier(DocumentManager::HYDRATE_CONVERTION_MODIFIER))){
                $modifiers = [];
            }
            
            $value = null;
            $fieldInfos = $this->getPropertyInfos($name);
            if (isset($fieldInfos["field"]) && isset($datas[$fieldInfos["field"]])) {
                if (isset($fieldInfos["embedded"])) {
                    $datas[$fieldInfos["field"]] = $this->convertEmbedded($datas[$fieldInfos["field"]], $fieldInfos["embedded"]);
                }

                if (isset($fieldInfos["multiEmbedded"])) {
                    $datas[$fieldInfos["field"]] = $this->convertEmbeddeds($datas[$fieldInfos["field"]], $fieldInfos["multiEmbedded"]);
                }

                foreach ($modifiers as $mod){
                    $datas[$fieldInfos["field"]] = call_user_func($mod, $datas[$fieldInfos["field"]]);
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
            $value = null;

            if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")) {
                $prop = $this->classMetadata->getProperty($name);
                $prop->setAccessible(true);
                $value = $prop->getValue($object);
                

                if (is_object($value) && $this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")) {
                    $embeddedClass = $this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")->document;
                    $hydrator = $this->getHydratorForEmbedded($embeddedClass);
                    $value = $hydrator->unhydrate($value);
                }

                if (is_array($value) && $this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\MultiEmbeddedDocument")) {
                    $hydrator = $this->getHydratorForEmbedded($this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\MultiEmbeddedDocument")->document);
                    $realValue = [];
                    foreach ($value as $embedded) {
                        $realValue[] = $hydrator->unhydrate($embedded);
                    }
                    $value = $realValue;
                }
                
                if(is_a($value, "\DateTime")){
                    $value = new \MongoDB\BSON\UTCDateTime($value->getTimestamp() * 1000);
                }
            }
            

            if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")) {
                $datas[$this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")->name] = $value;
            }
        }

        return $datas;
    }

    private function getPropertyInfos($name) {
        if (!isset($this->propertiesInfos[$name])) {
            $this->propertiesInfos[$name] = [];
            if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")) {
                $this->propertiesInfos[$name]["field"] = $this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\Field")->name;
                $this->fieldMapping[$this->propertiesInfos[$name]["field"]] = $name;
                if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")) {
                    $this->propertiesInfos[$name]["embedded"] = $this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument")->document;
                }
                if ($this->classMetadata->hasPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\MultiEmbeddedDocument")) {
                    $this->propertiesInfos[$name]["multiEmbedded"] = $this->classMetadata->getPropertyAnnotation($name, "JPC\MongoDB\ODM\Annotations\Mapping\MultiEmbeddedDocument")->document;
                }
            }
        }

        return $this->propertiesInfos[$name];
    }

    public function getFieldNameFor($name) {

        if (strstr($name, ".")) {
            list($name, $embeddedName) = explode(".", $name, 2);
        }


        if (!isset($this->fieldMapping[$name])) {
            if (false != ($realName = $this->searchFieldForName($name))) {
                $name = $realName;
            }
        }

        if (!isset($this->fieldMapping[$name])) {
            throw new Exception\MappingException("Unable to map '$name' with any MongoDB document field.");
        }

        if (isset($embeddedName) && null != ($propInfos = $this->propertiesInfos[$this->fieldMapping[$name]])) {
            if (isset($propInfos["embedded"])) {
                $name .= "." . $this->getHydratorForEmbedded($propInfos["embedded"])->getFieldNameFor($embeddedName);
            } else if (isset($propInfos["multiEmbedded"])) {
                $name .= "." . $this->getHydratorForEmbedded($propInfos["multiEmbedded"])->getFieldNameFor($embeddedName);
            } else {
                throw new Exception\MappingException("Unable to find field for '$name.$embeddedName', '$name' does not contain embedded(s) document(s).");
            }
        }

        return $name;
    }

    public function getEmbeddedClassFor($field) {
        if (!isset($this->fieldMapping[$field])) {
            throw new \Exception();
        } else {
            $field = $this->fieldMapping[$field];
            $embedded = isset($this->propertiesInfos[$field]["embedded"]) ? $this->propertiesInfos[$field]["embedded"] : isset($this->propertiesInfos[$field]["multiEmbedded"]) ? $this->propertiesInfos[$field]["multiEmbedded"] : false;
            return $embedded;
        }
    }

    private function searchFieldForName($name) {
        $propertiesNames = array_keys($this->classMetadata->getProperties());

        if (in_array($name, $propertiesNames)) {
            $datas = $this->getPropertyInfos($name);
            return isset($datas["field"]) ? $datas["field"] : null;
        } else {
            foreach ($propertiesNames as $propName) {
                $this->getPropertyInfos($propName);
            }
        }

        return false;
    }

    private function convertEmbedded($value, $embeddedDocument) {
        $object = new $embeddedDocument();

        if (!isset($this->embeddedReflectionClasses[$embeddedDocument])) {
            $this->embeddedReflectionClasses[$embeddedDocument] = new \ReflectionClass($embeddedDocument);
        }

        $hydrator = $this->getHydratorForEmbedded($embeddedDocument);

        $hydrator->hydrate($object, $value);

        return $object;
    }

    private function convertEmbeddeds($value, $embeddedDocument) {
        $embeddeds = [];
        foreach ($value as $embedded) {
            $embeddeds[] = $this->convertEmbedded($embedded, $embeddedDocument);
        }

        return $embeddeds;
    }

    public function getHydratorForField($field) {
        $emb = $this->getEmbeddedClassFor($field);
        return $this->getHydratorForEmbedded($emb);
    }

    private function getHydratorForEmbedded($embeddedClass) {
        if(!$embeddedClass){
            return false;
        }
        return Hydrator::instance($embeddedClass, Tools\ClassMetadataFactory::instance()->getMetadataForClass($embeddedClass));
    }

}
