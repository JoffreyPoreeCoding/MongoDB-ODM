<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM;

use axelitus\Patterns\Creational\Singleton;

/**
 * Description of ObjectManager
 *
 * @author poree
 */
class ObjectManager extends Singleton {
    
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
    private $objectStates = [];
    
    private $objects = [];
    
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
        $oid = spl_object_hash($object);
        
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
                $objectList[] = $object;
            }
        }
        
        return $objectList;
    }
    
}
