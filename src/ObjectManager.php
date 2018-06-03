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
     * Add an object
     *
     * @param   mixed   $object     Object to add
     * @param   int     $state      State of this object
     * @return  void
     */
    public function addObject($object, $state = self::OBJ_NEW)
    {
        $oid = spl_object_hash($object);

        if (!isset($this->objectStates[$oid])) {
            $this->objectStates[$oid] = $state;
            $this->objects[$oid] = $object;
        }
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

        if (!isset($this->objectStates[$oid])) {
            throw new Exception\StateException("Can't remove object, it does not be managed");
        }

        unset($this->objectStates[$oid]);
        unset($this->objects[$oid]);
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
        } else {
            return false;
        }

        if (!isset($this->objectStates[$oid])) {
            throw new Exception\StateException();
        }

        if (!in_array($state, [self::OBJ_MANAGED, self::OBJ_NEW, self::OBJ_REMOVED])) {
            throw new Exception\StateException("Invalid state '$state'");
        }

        if ($state == self::OBJ_REMOVED && $this->objectStates[$oid] == self::OBJ_NEW) {
            throw new Exception\StateException("Can't change state to removed because object is not managed. Insert it in database before");
        }

        $this->objectStates[$oid] = $state;

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

        if (isset($this->objectStates[$oid])) {
            return $this->objectStates[$oid];
        }

        return null;
    }

    /**
     * Get object with specified state
     *
     * @param   int     $state  State to search
     * @return  array
     */
    public function getObject($state = null)
    {
        if (!isset($state)) {
            return $this->objects;
        }

        $objectList = [];
        foreach ($this->objects as $oid => $object) {
            if ($this->objectStates[$oid] == $state) {
                $objectList[$oid] = $object;
            }
        }

        return $objectList;
    }

    /**
     * Check if object is managed
     *
     * @param   mixed   $object     Object to search
     * @return  boolean
     */
    public function hasObject($object)
    {
        return isset($this->objects[spl_object_hash($object)]);
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
