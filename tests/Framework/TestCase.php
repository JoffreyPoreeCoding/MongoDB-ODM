<?php

namespace JPC\Test\MongoDB\ODM\Framework;

class TestCase extends \PHPUnit_Framework_TestCase{
    
    protected function getPropertyValue($object, $propertyName){
        $prop = new \ReflectionProperty($object, $propertyName);
        $prop->setAccessible(true);
        
        return $prop->getValue($object);
    }
    
    protected function invokeMethod($object, $methodName, $params = []){
        $method = new \ReflectionMethod($object, $propertyName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $params);
    }
}
