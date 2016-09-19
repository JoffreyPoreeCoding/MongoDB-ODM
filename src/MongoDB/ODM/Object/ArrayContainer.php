<?php

namespace JPC\MongoDB\ODM\Object;

/**
 * Description of ArrayContainer
 *
 * @author poree
 */
class ArrayContainer {
    
    const VAL_ADDED = 1;
    const VAL_REMOVED = 2;
    
    private $array;
    
    private $added;
    
    function __construct($array = []) {
        $this->array = $array;
    }
    
    public function __debugInfo(){
        return [
            "array" => $this->array
        ];
    }
    
    public function add($value){
        $this->array[] = $value;
        
        end($this->array);
        $key = key($this->array);
        
        $this->added[] = $key;
    }
    
    public function remove($value){
        $key = array_search($value, $this->array);
        
        unset($key);
    }
    
    public function get($index){
        return $this->array[$index];
    }
    
    public function getArray(){
        return $this->array;
    }

}
