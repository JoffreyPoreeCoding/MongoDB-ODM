<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use JPC\MongoDB\ODM\Tools\ClassMetadata\Info\CollectionInfo;
use JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo;
use JPC\MongoDB\ODM\Tools\ClassMetadata\Info\ReferenceInfo;
use JPC\MongoDB\ODM\Tools\EventManager;

class ClassMetadata {
    /* ================================== */
    /*              CONSTANTS             */
    /* ================================== */

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
     * Namespace of the class
     * @var     string
     */
    private $namespace;

    /**
     * Show if all class metadas ar loaded
     * @var bool 
     */
    private $loaded = false;

    /**
     * Info about the collection
     * @var CollectionInfos
     */
    private $collectionInfo;

    /**
     * Infos about fields/propeties;
     * @var PropertyInfo[] 
     */
    private $propertiesInfos = [];

    /**
     * Tell if class has event
     * @var bool
     */
    private $hasEvent = false;

    /**
     * Event information
     * @var EventInfo
     */
    private $eventManager;

    /**
     * Create new ClassMetadata
     * 
     * @param   string              $className          Name of the class
     * @param   AnnotationReader    $reader             Annotation reader
     */
    public function __construct($className, $reader = null) {
        $this->reader = isset($reader) ? $reader : new CachedReader(new AnnotationReader(), new ApcuCache(), false);
        $this->name = $className;
    }
    
    public function getName(){
        return $this->name;
    }

    public function getNamespace(){
        if(!isset($this->namespace)){
            $reflectionClass = new \ReflectionClass($this->name);
            $this->namespace = $reflectionClass->getNamespaceName();
        }
        return $this->namespace;
    }

    public function getCollection() {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->collectionInfo->getCollection();
    }

    public function getBucketName(){
        if(!$this->loaded){
            $this->load();
        }

        return $this->collectionInfo->getBucketName();
    }
    
    public function getPropertyInfo($prop){
        if (!$this->loaded) {
            $this->load();
        }

        if(isset($this->propertiesInfos[$prop])){
            return $this->propertiesInfos[$prop];
        }

        return false;
    }

    public function getPropertiesInfos(){
        if (!$this->loaded) {
            $this->load();
        }

        return $this->propertiesInfos;
    }

    public function getPropertyInfoForField($field){
        if (!$this->loaded) {
            $this->load();
        }

        foreach ($this->propertiesInfos as $name => $infos) {
            if($infos->getField() == $field){
                return $infos;
            }
        }

        return false;
    }

    public function getPropertyForField($field){
        if (!$this->loaded) {
            $this->load();
        }

        foreach ($this->propertiesInfos as $name => $infos) {
            if($infos->getField() == $field){
                return new \ReflectionProperty($this->name, $name);
            }
        }

        return false;
    }

    public function getCollectionCreationOptions(){
        if (!$this->loaded) {
            $this->load();
        }

        return $this->collectionInfo->getCreationOptions();
    }

    public function getCollectionOptions(){
        if (!$this->loaded) {
            $this->load();
        }

        return $this->collectionInfo->getOptions();
    }

    public function setCollection($collection){
        if (!$this->loaded) {
            $this->load();
        }

        $this->collectionInfo->setCollection($collection);
        return $this;
    }

    public function getRepositoryClass(){
        if (!$this->loaded) {
            $this->load();
        }

        return $this->collectionInfo->getRepository();
    }

    public function getHydratorClass(){
        if (!$this->loaded) {
            $this->load();
        }

        return $this->collectionInfo->getHydrator();
    }

    public function getEventManager(){
        if (!$this->loaded) {
            $this->load();
        }

        return $this->eventManager;
    }


    private function load() {
        $reflectionClass = new \ReflectionClass($this->name);
        $this->collectionInfo = new CollectionInfo();

        foreach ($this->reader->getClassAnnotations($reflectionClass) as $annotation) {
            $this->processClassAnnotation($annotation);
        }

        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                if(!isset($this->propertiesInfos[$property->getName()])){
                    $this->propertiesInfos[$property->getName()] = new PropertyInfo();
                }
                $this->processPropertiesAnnotation($property->getName(), $annotation);
            }
        }

        $this->eventManager = new EventManager();
        if($this->hasEvent){
            $methods = $reflectionClass->getMethods();
            foreach($methods as $method){
                $annotations = $this->reader->getMethodAnnotations($method);
                if(!empty($annotations)){
                    foreach($annotations as $annotation){
                        if(in_array('JPC\MongoDB\ODM\Annotations\Event\Event', class_implements($annotation))){
                            $this->eventManager->add($annotation, $method->getName());
                        }
                    }
                }
            }
        }

        $this->loaded = true;
    }

    private function processClassAnnotation($annotation) {
        $class = get_class($annotation);
        switch ($class) {
            case "JPC\MongoDB\ODM\Annotations\Mapping\Document" :
            $this->collectionInfo->setCollection($annotation->collectionName);

            if (null !== ($rep = $annotation->repositoryClass)) {
                $this->collectionInfo->setRepository($annotation->repositoryClass);
            } else {
                $this->collectionInfo->setRepository("JPC\MongoDB\ODM\Repository");
            }

            if (null !== ($rep = $annotation->hydratorClass)) {
                $this->collectionInfo->setHydrator($annotation->hydratorClass);
            } else {
                $this->collectionInfo->setHydrator("JPC\MongoDB\ODM\Hydrator");
            }

            $this->checkCollectionCreationOptions($annotation);
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Document" :
            $this->collectionInfo->setBucketName($annotation->bucketName);
            $this->collectionInfo->setCollection($annotation->bucketName . ".files");

            if (null !== ($rep = $annotation->repositoryClass)) {
                $this->collectionInfo->setRepository($annotation->repositoryClass);
            } else {
                $this->collectionInfo->setRepository("JPC\MongoDB\ODM\GridFS\Repository");
            }

            if (null !== ($rep = $annotation->hydratorClass)) {
                $this->collectionInfo->setHydrator($annotation->hydratorClass);
            } else {
                $this->collectionInfo->setHydrator("JPC\MongoDB\ODM\GridFS\Hydrator");
            }
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\Option":
            $this->processOptionAnnotation($annotation);
            break;
            case "JPC\MongoDB\ODM\Annotations\Event\HasLifecycleCallbacks":
            $this->hasEvent = true;
            break;
        }
    }

    private function processOptionAnnotation(\JPC\MongoDB\ODM\Annotations\Mapping\Option $annotation){
        $options = [];
        if(isset($annotation->writeConcern)){
            $options["writeConcern"] = $annotation->writeConcern->getWriteConcern();
        }

        if(isset($annotation->readConcern)){
            $options["readConcern"] = $annotation->readConcern->getReadConcern();
        }

        if(isset($annotation->readPreference)){
            $options["readPreference"] = $annotation->readPreference->getReadPreference();
        }

        $this->collectionInfo->setOptions($options);
    }

    private function processPropertiesAnnotation($name, $annotation) {
        $class = get_class($annotation);
        switch ($class) {
            case "JPC\MongoDB\ODM\Annotations\Mapping\Id" :
            $this->propertiesInfos[$name]->setField("_id");
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\Field" :
            $this->propertiesInfos[$name]->setField($annotation->name);
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\EmbeddedDocument" :
            $this->propertiesInfos[$name]->setEmbedded(true);
            $this->propertiesInfos[$name]->setEmbeddedClass($annotation->document);
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\MultiEmbeddedDocument" :
            $this->propertiesInfos[$name]->setMultiEmbedded(true);
            $this->propertiesInfos[$name]->setEmbeddedClass($annotation->document);
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\Id" :
            $this->propertiesInfos[$name]->setField("_id");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Stream" :
            $this->propertiesInfos[$name]->setField("stream");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Filename" :
            $this->propertiesInfos[$name]->setField("filename");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Aliases" :
            $this->propertiesInfos[$name]->setField("aliases");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\ChunkSize" :
            $this->propertiesInfos[$name]->setField("chunkSize");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\UploadDate" :
            $this->propertiesInfos[$name]->setField("uploadDate");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Length" :
            $this->propertiesInfos[$name]->setField("length");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\ContentType" :
            $this->propertiesInfos[$name]->setField("contentType");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Md5" :
            $this->propertiesInfos[$name]->setField("md5");
            break;
            case "JPC\MongoDB\ODM\GridFS\Annotations\Mapping\Metadata" :
            $this->propertiesInfos[$name]->setMetadata(true);
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\RefersOne" :
            $referenceInfo = new ReferenceInfo();
            $referenceInfo->setDocument($annotation->document)->setCollection($annotation->collection);
            $this->propertiesInfos[$name]->setReferenceInfo($referenceInfo);
            break;
            case "JPC\MongoDB\ODM\Annotations\Mapping\RefersMany" :
            $referenceInfo = new ReferenceInfo();
            $referenceInfo->setIsMultiple(true)->setDocument($annotation->document)->setCollection($annotation->collection);
            $this->propertiesInfos[$name]->setReferenceInfo($referenceInfo);
            break;
        }
    }

    private function processMethodAnnotation(){

    }

    private function checkCollectionCreationOptions(\JPC\MongoDB\ODM\Annotations\Mapping\Document $annotation){
        $options = [];
        if($annotation->capped){
            $options["capped"] = true;
            $options["size"] = $annotation->size;

            if($annotation->max != false){
                $options["max"] = $annotation->max;
            }
        }

        $this->collectionInfo->setCreationOptions($options);
    }
}
