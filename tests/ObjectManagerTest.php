<?php

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\ObjectManager;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class ObjectManagerTest extends TestCase
{

    private $objectManager;

    public function setUp()
    {
        $this->objectManager = new ObjectManager();
    }

    /**
     * @test
     */
    public function addObject()
    {
        $object = new \stdClass();
        $this->objectManager->addObject($object);

        $oid = spl_object_hash($object);

        $objects = $this->getPrivatePropValue($this->objectManager, "objects");
        $objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");

        $this->assertArrayHasKey($oid, $objects);
        $this->assertArrayHasKey($oid, $objectStates);
        $this->assertEquals($object, $objects[$oid]);
        $this->assertEquals(ObjectManager::OBJ_NEW, $objectStates[$oid]);
    }

    /**
     * @test
     */
    public function removeObjectInexisting()
    {
        $object = new \stdClass();

        $this->expectException("JPC\MongoDB\ODM\Exception\StateException");
        $this->objectManager->removeObject($object);
    }

    /**
     * @test
     */
    public function removeObject()
    {
        $object = new \stdClass();
        $this->objectManager->addObject($object);

        $oid = spl_object_hash($object);
        $this->objectManager->removeObject($object);

        $objects = $this->getPrivatePropValue($this->objectManager, "objects");
        $objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");

        $this->assertArrayNotHasKey($oid, $objects);
        $this->assertArrayNotHasKey($oid, $objectStates);
    }

    /**
     * @test
     */
    public function setObjectStateOk()
    {
        $object = new \stdClass();
        $this->objectManager->addObject($object);

        $oid = spl_object_hash($object);

        $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
        $objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");
        $this->assertEquals(ObjectManager::OBJ_MANAGED, $objectStates[$oid]);

        $this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
        $objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");
        $this->assertEquals(ObjectManager::OBJ_REMOVED, $objectStates[$oid]);
    }

    /**
     * @test
     */
    public function setObjectStateNok()
    {
        $object = new \stdClass();
        $this->objectManager->addObject($object);

        $oid = spl_object_hash($object);

        $this->expectException("JPC\MongoDB\ODM\Exception\StateException");
        $this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
        $objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");
    }

    /**
     * @test
     */
    public function getObjectState()
    {
        $object = new \stdClass();
        $this->assertNull($this->objectManager->getObjectState($object));
        $this->objectManager->addObject($object);
        $this->assertEquals(ObjectManager::OBJ_NEW, $this->objectManager->getObjectState($object));
    }

    /**
     * @test
     */
    public function getObject()
    {
        $object1 = new \stdClass();
        $this->objectManager->addObject($object1);

        $object2 = new \stdClass();
        $this->objectManager->addObject($object2, ObjectManager::OBJ_MANAGED);
        $object3 = new \stdClass();
        $this->objectManager->addObject($object3, ObjectManager::OBJ_MANAGED);

        $object4 = new \stdClass();
        $this->objectManager->addObject($object4, ObjectManager::OBJ_REMOVED);
        $object5 = new \stdClass();
        $this->objectManager->addObject($object5, ObjectManager::OBJ_REMOVED);
        $object6 = new \stdClass();
        $this->objectManager->addObject($object6, ObjectManager::OBJ_REMOVED);

        $this->assertCount(6, $this->objectManager->getObject());
        $this->assertCount(1, $this->objectManager->getObject(ObjectManager::OBJ_NEW));
        $this->assertCount(2, $this->objectManager->getObject(ObjectManager::OBJ_MANAGED));
        $this->assertCount(3, $this->objectManager->getObject(ObjectManager::OBJ_REMOVED));
    }

    /**
     * @test
     */
    public function clear()
    {
        $object1 = new \stdClass();
        $this->objectManager->addObject($object1);
        $object2 = new \stdClass();
        $this->objectManager->addObject($object2, ObjectManager::OBJ_MANAGED);
        $object3 = new \stdClass();
        $this->objectManager->addObject($object3, ObjectManager::OBJ_MANAGED);
        $object4 = new \stdClass();
        $this->objectManager->addObject($object4, ObjectManager::OBJ_REMOVED);
        $object5 = new \stdClass();
        $this->objectManager->addObject($object5, ObjectManager::OBJ_REMOVED);
        $object6 = new \stdClass();
        $this->objectManager->addObject($object6, ObjectManager::OBJ_REMOVED);

        $this->assertCount(6, $this->getPrivatePropValue($this->objectManager, "objects"));
        $this->objectManager->clear();
        $this->assertCount(0, $this->getPrivatePropValue($this->objectManager, "objects"));
    }

    private function getPrivatePropValue($object, $propertyName)
    {
        $reflProp = new \ReflectionProperty($object, $propertyName);
        $reflProp->setAccessible(true);
        return $reflProp->getValue($object);
    }
}
