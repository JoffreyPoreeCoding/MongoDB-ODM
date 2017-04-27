<?php

namespace JPC\MongoDB\ODM;

/**
 * Description of ObjectManager
 *
 * @author poree
 */
class ObjectManager {
    
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
    
    protected $objects = [];

    public function addObject($object, $state = self::OBJ_NEW){
        $oid = spl_object_hash($object);
        
        if(!isset($this->objectStates[$oid])){
            $this->objectStates[$oid] = $state;
            $this->objects[$oid] = $object;
        }
    }

    public function removeObject($object){
        $oid = spl_object_hash($object);
        
        if(!isset($this->objectStates[$oid])){
            throw new Exception\StateException("Can't remove object, it does not be managed");
        }
        
        unset($this->objectStates[$oid]);
        unset($this->objects[$oid]);
    }
    
    public function setObjectState($object, $state){
        if(is_object($object)){
        	$oid = spl_object_hash($object);
	} else {
		return false;
	}
        
        if(!isset($this->objectStates[$oid])){
            throw new Exception\StateException();
        }
        
        if(!in_array($state, [self::OBJ_MANAGED, self::OBJ_NEW, self::OBJ_REMOVED])){
            throw new Exception\StateException("Invalid state '$state'");
        }
        
        if($state == self::OBJ_REMOVED && $this->objectStates[$oid] == self::OBJ_NEW){
            throw new Exception\StateException("Can't change state to removed because object is not managed. Insert it in database before");
        }
        
        $this->objectStates[$oid] = $state;

	return true;
    }
    
    public function getObjectState($object){
        $oid = spl_object_hash($object);
        
        if(isset($this->objectStates[$oid])){
            return $this->objectStates[$oid];
        }
        
        return null;
    }
    
    public function getObject($state = null){
        if(!isset($state)){
            return $this->objects;
        }
        
        $objectList = [];
        foreach ($this->objects as $oid => $object) {
            if($this->objectStates[$oid] == $state){
                $objectList[$oid] = $object;
            }
        }
        
        return $objectList;
    }

    public function hasObject($object){
        return isset($this->objects[spl_object_hash($object)]);
    }
    
    public function clear(){
        $this->objectStates = [];
        $this->objects = [];
    }
    
}
