<?php

namespace JPC\MongoDB\ODM;

/**
 * Manage persisted objects
 */
class ObjectManager
{

    /**
     * Object states
     */
    const OBJ_NEW = 1;
    const OBJ_MANAGED = 2;
    const OBJ_REMOVED = 3;

    /**
     * Contains object states with object id as key
     * @var array
     */
    protected $objectStates = [];

    /**
     * Persisted Objects
     * @var array
     */
    protected $objects = [];

    /**
     * Store repository associated with object (for flush on special collection)
     * @var array
     */
    protected $objectsRepository = [];

    /**
     * Add an object
     *
     * @param   mixed   $object     Object to add
     * @param   int     $state      State of this object
     * @return  void
     */
    public function addObject($object, $state, $repository)
    {
        $data = $repository->getHydrator()->unhydrate($object);
        $oid = spl_object_hash($object);
        $id = isset($data['_id']) ? serialize($data['_id']) . $repository->getCollection() : $oid;

        if (!isset($this->objectStates[$id])) {
            $this->objectStates[$id] = $state;
        }
        $this->objects[$id] = $object;
        $this->objectsRepository[$oid] = $repository;
    }

    /**
     * Unpersist object
     *
     * @param   mixed   $object     Object to unpersist
     * @return  void
     */
    public function removeObject($object)
    {
        $oid = spl_object_hash($object);
        $data = [];
        if (isset($this->objectsRepository[$oid])) {
            $repository = $this->objectsRepository[$oid];
            $data = $repository->getHydrator()->unhydrate($object);
        }
        $id = isset($data['_id']) ? serialize($data['_id']) . $repository->getCollection() : $oid;

        if (!isset($this->objectStates[$id])) {
            throw new Exception\StateException("Can't remove object, it does not be managed");
        }

        unset($this->objectStates[$id]);
        unset($this->objects[$id]);
        unset($this->objectsRepository[$id]);
        unset($this->objectStates[$oid]);
        unset($this->objects[$oid]);
        unset($this->objectsRepository[$oid]);
    }

    /**
     * Update object state
     *
     * @param   mixed   $object     Object to change state
     * @param   int     $state      New state
     * @return  void
     */
    public function setObjectState($object, $state)
    {
        if (is_object($object)) {
            $oid = spl_object_hash($object);
            $repository = $this->objectsRepository[$oid];
            $data = $repository->getHydrator()->unhydrate($object);
            $id = isset($data['_id']) ? serialize($data['_id']) . $repository->getCollection() : $oid;
        } else {
            return false;
        }

        if (!isset($this->objectStates[$id]) && !isset($this->objectStates[$oid])) {
            throw new Exception\StateException();
        }

        if (!in_array($state, [self::OBJ_MANAGED, self::OBJ_NEW, self::OBJ_REMOVED])) {
            throw new Exception\StateException("Invalid state '$state'");
        }

        if ($state == self::OBJ_REMOVED && isset($this->objectStates[$oid]) && $this->objectStates[$oid] == self::OBJ_NEW) {
            throw new Exception\StateException("Can't change state to removed because object is not managed. Insert it in database before");
        }

        if (isset($this->objectStates[$oid])) {
            unset($this->objectStates[$oid]);
            unset($this->objects[$oid]);
        }

        $this->objectStates[$id] = $state;
        $this->objects[$id] = $object;

        return true;
    }

    /**
     * Get object state
     *
     * @param   mixed   $object
     * @return  void
     */
    public function getObjectState($object)
    {
        $oid = spl_object_hash($object);
        if (isset($this->objectsRepository[$oid])) {
            $repository = $this->objectsRepository[$oid];
            $data = $repository->getHydrator()->unhydrate($object);
            $id = isset($data['_id']) ? serialize($data['_id']) . $repository->getCollection() : $oid;

            if (isset($this->objectStates[$id])) {
                return $this->objectStates[$id];
            }
        }

        return null;
    }

    /**
     * Get object with specified state
     *
     * @param   int     $state  State to search
     * @return  array
     */
    public function getObjects($state = null)
    {
        if (!isset($state)) {
            return $this->objects;
        }

        $objectList = [];
        foreach ($this->objects as $id => $object) {
            if ($this->objectStates[$id] == $state) {
                $objectList[$id] = $object;
            }
        }

        return $objectList;
    }

    public function getObject($id)
    {
        if (isset($this->objects[$id])) {
            return $this->objects[$id];
        }
    }

    /**
     * Check if object is managed
     *
     * @param   mixed   $object     Object to search
     * @return  boolean
     */
    public function hasObject($object)
    {
        $oid = spl_object_hash($object);
        $repository = $this->objectsRepository[$oid];
        $data = $repository->getHydrator()->unhydrate($object);
        $id = isset($data['_id']) ? serialize($data['_id']) . $repository->getCollection() : $oid;

        return isset($this->objects[$oid]) || isset($this->objects[$id]);
    }

    /**
     * Clear all object states
     *
     * @return void
     */
    public function clear()
    {
        $this->objectStates = [];
        $this->objects = [];
    }
}
