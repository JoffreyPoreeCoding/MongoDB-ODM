<?php

namespace JPC\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use axelitus\Patterns\Creational\Multiton;
use JPC\MongoDB\ODM\ObjectManager;

/**
 * Allow to find, delete, document in MongoDB
 *
 * @author poree
 */
class Repository extends Multiton {

    /**
     * Hydrator of model
     * @var Hydrator
     */
    private $hydrator;

    /**
     *
     * @var \MongoDB\Collection
     */
    private $collection;

    /**
     * Object Manager
     * @var ObjectManager
     */
    private $om;

    public function __construct($classMetadata) {
        $this->modelName = $classMetadata->getName();

        $this->hydrator = Hydrator::instance($this->modelName, $classMetadata);

        $this->collection = DocumentManager::instance()->getMongoDBDatabase()->selectCollection($classMetadata->getClassAnnotation("JPC\MongoDB\ODM\Annotations\Mapping\Document")->collectionName);

        $this->om = ObjectManager::instance();
    }

    public function getHydrator() {
        return $this->hydrator;
    }

    public function count($filters = [], $options = []) {
        return $this->collection->count($filters, $options);
    }

    public function find($id, $projections = [], $options = [], $autopersist = true) {
        $result = $this->collection->findOne(["_id" => $id], $options);

        $object = new $this->modelName();
        $this->hydrator->hydrate($object, $result);

        if ($autopersist) {
            $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
        }

        return $object;
    }

    public function findAll($projections = [], $sorts = [], $options = [], $autopersist = true) {
        $result = $this->collection->find([], $options);

        $objects = [];

        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $objects[] = $object;

            if ($autopersist) {
                $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
            }
        }

        return $objects;
    }

    public function findBy($filters, $projections = [], $sorts = [], $options = [], $autopersist = true) {
        $this->castAllQueries($filters, $projections, $sorts);

        $result = $this->collection->find($filters, $options);

        echo "<br/>" . (microtime(TRUE) - $GLOBALS["start"]) . " to request Mongo<br/>";

        $objects = [];

        foreach ($result as $datas) {
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $datas);
            $objects[] = $object;

            if ($autopersist) {
                $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
            }
        }

        return $objects;
    }

    public function findOneBy($filters = [], $projections = [], $sorts = [], $options = [], $autopersist = true) {
        $result = $this->collection->findOne($filters, $options);

        $object = new $this->modelName();
        
        $this->hydrator->hydrate($object, $result);

        if ($autopersist) {
            $this->om->addObject($object, ObjectManager::OBJ_MANAGED);
        }

        return $object;
    }

    public function distinct($fieldName, $filters = [], $options = []) {
        $result = $this->collection->distinct($fieldName, $filters, $options);

        return $result;
    }

    public function drop() {
        $result = $this->collection->drop();

        if ($result->ok) {
            return true;
        } else {
            return false;
        }
    }

    private function castMongoQuery($query, $hydrator = null) {
        if (!isset($hydrator)) {
            $hydrator = $this->hydrator;
        }
        $new_query = [];
        foreach ($query as $name => $value) {
            $field = $hydrator->getFieldNameFor($name);
            if (is_array($value)) {
                $value = $this->castMongoQuery($value, $this->hydrator->getHydratorForField($field));
            }
            $new_query[$field] = $value;
        }
        return $new_query;
    }

    private function castAllQueries(&$filters, &$projection = [], &$sort = []) {
        $filters = $this->castMongoQuery($filters);
        $projection = $this->castMongoQuery($projection);
        $sort = $this->castMongoQuery($sort);
    }
    
    /**
     * 
     * @return \MongoDB\Collection
     */
    public function getCollection(){
        return $this->collection;
    }

}
