<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Tools\ClassMetadata;

class Hydrator {

    use \JPC\DesignPattern\Multiton;

    /**
     * Document Manager
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * Class metadatas
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * Create new Hydrator
     * @param   DocumentManager     $documentManager    Document manager of the repository
     * @param   ClassMetadata       $classMetadata      Class metadata corresponding to class
     */
    function __construct(DocumentManager $documentManager, ClassMetadata $classMetadata) {
        $this->documentManager = $documentManager;
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

                if (($datas[$field] instanceof \MongoDB\Model\BSONDocument) && $infos->getEmbedded() && null !== ($class = $infos->getEmbeddedClass())) {
                    $embedded = new $class();
                    $this->getHydrator($class)->hydrate($embedded, $datas[$field]);
                    $datas[$field] = $embedded;
                }

                if (($datas[$field] instanceof \MongoDB\Model\BSONArray) && $infos->getMultiEmbedded() && null !== ($class = $infos->getEmbeddedClass())) {
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

            if (is_object($value) && $infos->getEmbedded()) {
                $value = $this->getHydrator($infos->getEmbeddedClass())->unhydrate($value);
            }

            if (is_array($value) && $infos->getMultiEmbedded()) {
                $array = [];
                foreach ($value as $embeddedValue) {
                    $array[] = $this->getHydrator($infos->getEmbeddedClass())->unhydrate($embeddedValue);
                }
                $value = $array;
            }

            $datas[$infos->getField()] = $value;
        }

        return $datas;
    }

    /**
     * Get hydrator for specified class
     * @param   string              $class              Class which you will get hydrator
     * @return  Hydrator            Hydrator corresponding to specified class
     */
    public function getHydrator($class) {
        $metadata = Tools\ClassMetadataFactory::getInstance()->getMetadataForClass($class);
        return Hydrator::getInstance($class, $this->documentManager, $metadata);
    }

}
