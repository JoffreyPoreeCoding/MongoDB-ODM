<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
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
     * Document Manager
     * @var DocumentManager
     */
    protected $documentManager; 

    /**
     * Repository factory (Used for referenced fields)
     * @var RepositoryFactory
     */
    protected $repositoryFactory;

    /**
     * Create new Hydrator
     * @param   DocumentManager     $classMetadataFactory    Document manager of the repository
     * @param   ClassMetadata       $classMetadata      Class metadata corresponding to class
     */
    function __construct(ClassMetadataFactory $classMetadataFactory, ClassMetadata $classMetadata, DocumentManager $documentManager, RepositoryFactory $repositoryFactory) {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->classMetadata = $classMetadata;
        $this->documentManager = $documentManager;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * Hydrate on object
     * @param   object              $object             Object to hydrate
     * @param   array               $datas              Data which will hydrate the object
     */
    function hydrate(&$object, $datas, $maxReferenceDeep = 10) {
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

                if(null !== ($refInfos = $infos->getReferenceInfo()) && !$refInfos->getIsMultiple() && $maxReferenceDeep > 0){
                    $repository = $this->repositoryFactory->getRepository($this->documentManager, $refInfos->getDocument(), $refInfos->getCollection());

                    $objectDatas = $repository->getCollection()->findOne(["_id" => $datas[$field]]);

                    $referedObject = null;

                    if(isset($objectDatas)){
                        $class = $refInfos->getDocument();

                        if(!class_exists($class)){
                            $class = $this->classMetadata->getNamespace() . "\\" . $class;
                        }

                        $referedObject = new $class();

                        $hydrator = $this->getHydrator($refInfos->getDocument());
                        $hydrator->hydrate($referedObject, $objectDatas, $maxReferenceDeep - 1);

                    }
                    $datas[$field] = $referedObject;
                }

                if(null !== ($refInfos = $infos->getReferenceInfo()) && $refInfos->getIsMultiple()){
                    $repository = $this->repositoryFactory->getRepository($this->documentManager, $refInfos->getDocument(), $refInfos->getCollection());


                    if(!$datas[$field] instanceof \MongoDB\Model\BSONArray && !is_array($datas[$field])){
                        throw new \Exception("RefersMany value must be an array for document with '_id' : " . $datas["_id"]);
                    } else {
                        $objectsDatas = $repository->getCollection()->find(["_id" => ['$in' => $datas[$field]]]);
                    }

                    $objectArray = null;

                    if(!empty($objectsDatas)){
                        $objectArray = [];
                        foreach($objectsDatas as $objectDatas){
                            $class = $refInfos->getDocument();

                            if(!class_exists($class)){
                                $class = $this->classMetadata->getNamespace() . "\\" . $class;
                            }

                            $referedObject = new $class();

                            $hydrator = $this->getHydrator($refInfos->getDocument());
                            $hydrator->hydrate($referedObject, $objectDatas, $maxReferenceDeep - 1);

                            $objectArray[] = $referedObject;
                        }
                    }
                    $datas[$field] = $objectArray;
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

            if(($value instanceof \MongoDB\Model\BSONDocument) || ($value instanceof \MongoDB\Model\BSONArray)){
                $value = $this->recursiveConvertInArray((array) $value);
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

            if(is_object($value) && null != ($refInfos = $infos->getReferenceInfo()) && !$refInfos->getIsMultiple()) {
                $class = $refInfos->getDocument();
                if(!class_exists($class)){
                    $class = $this->classMetadata->getNamespace() . "\\" . $class;
                }

                $value = $this->getHydrator($class)->unhydrate($value)["_id"];
            }

            if (is_array($value) && null != ($refInfos = $infos->getReferenceInfo()) && $refInfos->getIsMultiple()) {
                $array = [];
                foreach ($value as $referedValue) {
                    $class = $refInfos->getDocument();
                    if(!class_exists($class)){
                        $class = $this->classMetadata->getNamespace() . "\\" . $class;
                    }
                    $array[] = $this->getHydrator($class)->unhydrate($referedValue)["_id"];
                }
                $value = $array;
            }

            if($value instanceof \DateTime){
                $value = new \MongoDB\BSON\UTCDateTime($value->getTimestamp() * 1000);
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
        return new Hydrator($this->classMetadataFactory, $metadata, $this->documentManager, $this->repositoryFactory);
    }

}
