<?php

namespace JPC\Test\MongoDB\ODM\Framework;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    protected function getPropertyValue($object, $propertyName)
    {
        $prop = new \ReflectionProperty($object, $propertyName);
        $prop->setAccessible(true);

        return $prop->getValue($object);
    }

    protected function invokeMethod($object, $methodName, $params = [])
    {
        $method = new \ReflectionMethod($object, $propertyName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $params);
    }
}
