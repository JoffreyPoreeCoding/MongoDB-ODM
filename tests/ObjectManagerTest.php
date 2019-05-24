<?php

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class ObjectManagerTest extends TestCase
{

    private $objectManager;

    public function setUp()
    {
        $this->objectManager = new ObjectManager();
    }

    public function testAddObject()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();
        $this->objectManager->addObject($object, ObjectManager::OBJ_NEW, $repositoryMock);

        $oid = spl_object_hash($object);

        $objects = $this->getPropertyValue($this->objectManager, "objects");
        $objectStates = $this->getPropertyValue($this->objectManager, "objectStates");

        $this->assertArrayHasKey($oid, $objects);
        $this->assertArrayHasKey($oid, $objectStates);
        $this->assertEquals($object, $objects[$oid]);
        $this->assertEquals(ObjectManager::OBJ_NEW, $objectStates[$oid]);
    }

    public function testRemoveObjectInexisting()
    {
        $object = new \stdClass();

        $this->expectException("JPC\MongoDB\ODM\Exception\StateException");
        $this->objectManager->removeObject($object);
    }

    public function testRemoveObject()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();
        $this->objectManager->addObject($object, ObjectManager::OBJ_NEW, $repositoryMock);

        $oid = spl_object_hash($object);
        $this->objectManager->removeObject($object);

        $objects = $this->getPropertyValue($this->objectManager, "objects");
        $objectStates = $this->getPropertyValue($this->objectManager, "objectStates");

        $this->assertArrayNotHasKey($oid, $objects);
        $this->assertArrayNotHasKey($oid, $objectStates);
    }

    public function testSetObjectStateOk()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();
        $this->objectManager->addObject($object, ObjectManager::OBJ_NEW, $repositoryMock);

        $oid = spl_object_hash($object);

        $this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
        $objectStates = $this->getPropertyValue($this->objectManager, "objectStates");
        $this->assertEquals(ObjectManager::OBJ_MANAGED, $objectStates[$oid]);

        $this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
        $objectStates = $this->getPropertyValue($this->objectManager, "objectStates");
        $this->assertEquals(ObjectManager::OBJ_REMOVED, $objectStates[$oid]);
    }

    public function testSetObjectStateNok()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();
        $this->objectManager->addObject($object, ObjectManager::OBJ_NEW, $repositoryMock);

        $oid = spl_object_hash($object);

        $this->expectException("JPC\MongoDB\ODM\Exception\StateException");
        $this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
        $objectStates = $this->getPropertyValue($this->objectManager, "objectStates");
    }

    public function testGetObjectState()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object = new \stdClass();
        $this->assertNull($this->objectManager->getObjectState($object));
        $this->objectManager->addObject($object, ObjectManager::OBJ_NEW, $repositoryMock);
        $this->assertEquals(ObjectManager::OBJ_NEW, $this->objectManager->getObjectState($object));
    }

    public function testGetObjects()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object1 = new \stdClass();
        $this->objectManager->addObject($object1, ObjectManager::OBJ_NEW, $repositoryMock);

        $object2 = new \stdClass();
        $this->objectManager->addObject($object2, ObjectManager::OBJ_MANAGED, $repositoryMock);
        $object3 = new \stdClass();
        $this->objectManager->addObject($object3, ObjectManager::OBJ_MANAGED, $repositoryMock);

        $object4 = new \stdClass();
        $this->objectManager->addObject($object4, ObjectManager::OBJ_REMOVED, $repositoryMock);
        $object5 = new \stdClass();
        $this->objectManager->addObject($object5, ObjectManager::OBJ_REMOVED, $repositoryMock);
        $object6 = new \stdClass();
        $this->objectManager->addObject($object6, ObjectManager::OBJ_REMOVED, $repositoryMock);

        $this->assertCount(6, $this->objectManager->getObjects());
        $this->assertCount(1, $this->objectManager->getObjects(ObjectManager::OBJ_NEW));
        $this->assertCount(2, $this->objectManager->getObjects(ObjectManager::OBJ_MANAGED));
        $this->assertCount(3, $this->objectManager->getObjects(ObjectManager::OBJ_REMOVED));
    }

    public function testClear()
    {
        $repositoryMock = $this->createMock(Repository::class);
        $hydratorMock = $this->createMock(Hydrator::class);
        $repositoryMock->method('getHydrator')->willReturn($hydratorMock);
        $hydratorMock->method('unhydrate')->willReturn(['field' => 'value']);

        $object1 = new \stdClass();
        $this->objectManager->addObject($object1, ObjectManager::OBJ_NEW, $repositoryMock);
        $object2 = new \stdClass();
        $this->objectManager->addObject($object2, ObjectManager::OBJ_MANAGED, $repositoryMock);
        $object3 = new \stdClass();
        $this->objectManager->addObject($object3, ObjectManager::OBJ_MANAGED, $repositoryMock);
        $object4 = new \stdClass();
        $this->objectManager->addObject($object4, ObjectManager::OBJ_REMOVED, $repositoryMock);
        $object5 = new \stdClass();
        $this->objectManager->addObject($object5, ObjectManager::OBJ_REMOVED, $repositoryMock);
        $object6 = new \stdClass();
        $this->objectManager->addObject($object6, ObjectManager::OBJ_REMOVED, $repositoryMock);

        $this->assertCount(6, $this->getPropertyValue($this->objectManager, "objects"));
        $this->objectManager->clear();
        $this->assertCount(0, $this->getPropertyValue($this->objectManager, "objects"));
    }
}
