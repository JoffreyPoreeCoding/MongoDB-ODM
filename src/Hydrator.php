<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class Hydrator {

    /**
     * Document Manager
     * @var DocumentManager
     */
    protected $classMetadataFactory;

    /**
     * Class metadatas
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * Create new Hydrator
     * @param   DocumentManager     $classMetadataFactory    Document manager of the repository
     * @param   ClassMetadata       $classMetadata      Class metadata corresponding to class
     */
    function __construct(ClassMetadataFactory $classMetadataFactory, ClassMetadata $classMetadata) {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->classMetadata = $classMetadata;
    }

    /**
     * Hydrate on object
     * @param   object              $object             Object to hydrate
     * @param   array               $datas              Data which will hydrate the object
     */
    function hydrate(&$object, $datas) {
        if($datas instanceof \MongoDB\Model\BSONArray || $datas instanceof \MongoDB\Model\BSONDocument){
            $datas = (array) $datas;
        }
        $properties = $this->classMetadata->getPropertiesInfos();

        foreach ($properties as $name => $infos) {
            if (null !== ($field = $infos->getField()) && is_array($datas) && array_key_exists($field, $datas) && $datas[$field] !== null) {
                
                $prop = new \ReflectionProperty($this->classMetadata->getName(), $name);
                $prop->setAccessible(true);

                if ((($datas[$field] instanceof \MongoDB\Model\BSONDocument) || is_array($datas[$field])) && $infos->getEmbedded() && null !== ($class = $infos->getEmbeddedClass())) {
                    if(!class_exists($class)){
                        $class = $this->classMetadata->getNamespace() . "\\" . $class;
                    }
                    $embedded = new $class();
                    $this->getHydrator($class)->hydrate($embedded, $datas[$field]);
                    $datas[$field] = $embedded;
                }

                if ((($datas[$field] instanceof \MongoDB\Model\BSONArray) || is_array($datas[$field])) && $infos->getMultiEmbedded() && null !== ($class = $infos->getEmbeddedClass())) {
                    if(!class_exists($class)){
                        $class = $this->classMetadata->getNamespace() . "\\" . $class;
                    }
                    $array = [];
                    foreach ($datas[$field] as $value) {
                        if($value === null) continue;
                        $embedded = new $class();
                        $this->getHydrator($class)->hydrate($embedded, $value);
                        $array[] = $embedded;
                    }
                    $datas[$field] = $array;
                }

                $prop->setValue($object, $datas[$field]);
            }
        }
    }

    /**
     * Unhydrate an object
     * @param   object              $object             Object to unhydrate
     * @return  array               Unhydrated Object
     */
    function unhydrate($object) {
        $properties = $this->classMetadata->getPropertiesInfos();
        $datas = [];

        foreach ($properties as $name => $infos) {
            $prop = new \ReflectionProperty($this->classMetadata->getName(), $name);
            $prop->setAccessible(true);

            $value = $prop->getValue($object);

            if(null === $value){
                continue;
            }

            if (is_object($value) && $infos->getEmbedded()) {
                $class = $infos->getEmbeddedClass();
                if(!class_exists($class)){
                    $class = $this->classMetadata->getNamespace() . "\\" . $class;
                }
                $value = $this->getHydrator($class)->unhydrate($value);
            }

            if (is_array($value) && $infos->getMultiEmbedded()) {
                $array = [];
                foreach ($value as $embeddedValue) {
                    $class = $infos->getEmbeddedClass();
                    if(!class_exists($class)){
                        $class = $this->classMetadata->getNamespace() . "\\" . $class;
                    }
                    $array[] = $this->getHydrator($class)->unhydrate($embeddedValue);
                }
                $value = $array;
            }
            
            if($value instanceof \DateTime){
                $value = new \MongoDB\BSON\UTCDateTime($value->getTimestamp() * 1000);
            }
            
            if(($value instanceof \MongoDB\Model\BSONDocument) || ($value instanceof \MongoDB\Model\BSONArray)){
                $value = $this->recursiveConvertInArray((array) $value);
            } 

            $datas[$infos->getField()] = $value;
        }

        return $datas;
    }

    public function recursiveConvertInArray($array){
        $newArray = [];
        foreach ($array as $key => $value) {
            if($value instanceof \MongoDB\Model\BSONDocument || $value instanceof \MongoDB\Model\BSONArray){
                $value = (array) $value;
            }

            if(is_array($value)){
                $value = $this->recursiveConvertInArray($value);
            }

            $newArray[$key] = $value;
        }

        return $newArray;
    }

    /**
     * Get hydrator for specified class
     * 
     * @param   string              $class              Class which you will get hydrator
     * @return  Hydrator            Hydrator corresponding to specified class
     */
    public function getHydrator($class) {
        $metadata = $this->classMetadataFactory->getMetadataForClass($class);
        return new Hydrator($this->classMetadataFactory, $metadata);
    }

}
