<?php

namespace JPC\Test\MongoDB\ODM\ObjectManager;

use JPC\MongoDB\ODM\ObjectManager;
use JPC\Test\MongoDB\ODM\Model\ObjectMapping;
use PHPUnit\Framework\TestCase;

class ObjectManagerTest extends TestCase {

	private $objectManager;

	public function setUp(){
		$this->objectManager = new ObjectManager();
	}

	public function test_addObject(){
		$object = new ObjectMapping();
		$this->objectManager->addObject($object);

		$oid = spl_object_hash($object);

		$objects = $this->getPrivatePropValue($this->objectManager, "objects");
		$objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");

		$this->assertArrayHasKey($oid, $objects);
		$this->assertArrayHasKey($oid, $objectStates);
		$this->assertEquals($object, $objects[$oid]);
		$this->assertEquals(ObjectManager::OBJ_NEW, $objectStates[$oid]);
	}

	public function test_removeObject_inexisting(){
		$object = new ObjectMapping();

		$this->expectException("JPC\MongoDB\ODM\Exception\StateException");
		$this->objectManager->removeObject($object);
	}

	public function test_removeObject(){
		$object = new ObjectMapping();
		$this->objectManager->addObject($object);

		$oid = spl_object_hash($object);
		$this->objectManager->removeObject($object);

		$objects = $this->getPrivatePropValue($this->objectManager, "objects");
		$objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");

		$this->assertArrayNotHasKey($oid, $objects);
		$this->assertArrayNotHasKey($oid, $objectStates);
	}

	public function test_setObjectState_Ok(){
		$object = new ObjectMapping();
		$this->objectManager->addObject($object);

		$oid = spl_object_hash($object);

		$this->objectManager->setObjectState($object, ObjectManager::OBJ_MANAGED);
		$objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");
		$this->assertEquals(ObjectManager::OBJ_MANAGED, $objectStates[$oid]);

		$this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
		$objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");
		$this->assertEquals(ObjectManager::OBJ_REMOVED, $objectStates[$oid]);
	}

	public function test_setObjectState_Nok(){
		$object = new ObjectMapping();
		$this->objectManager->addObject($object);

		$oid = spl_object_hash($object);

		$this->expectException("JPC\MongoDB\ODM\Exception\StateException");
		$this->objectManager->setObjectState($object, ObjectManager::OBJ_REMOVED);
		$objectStates = $this->getPrivatePropValue($this->objectManager, "objectStates");
	}

	public function test_getObjectState(){
		$object = new ObjectMapping();
		$this->assertNull($this->objectManager->getObjectState($object));
		$this->objectManager->addObject($object);
		$this->assertEquals(ObjectManager::OBJ_NEW, $this->objectManager->getObjectState($object));
	}

	public function test_getObject(){
		$object1 = new ObjectMapping();
		$this->objectManager->addObject($object1);

		$object2 = new ObjectMapping();
		$this->objectManager->addObject($object2, ObjectManager::OBJ_MANAGED);
		$object3 = new ObjectMapping();
		$this->objectManager->addObject($object3, ObjectManager::OBJ_MANAGED);

		$object4 = new ObjectMapping();
		$this->objectManager->addObject($object4, ObjectManager::OBJ_REMOVED);
		$object5 = new ObjectMapping();
		$this->objectManager->addObject($object5, ObjectManager::OBJ_REMOVED);
		$object6 = new ObjectMapping();
		$this->objectManager->addObject($object6, ObjectManager::OBJ_REMOVED);

		$this->assertCount(6, $this->objectManager->getObject());
		$this->assertCount(1, $this->objectManager->getObject(ObjectManager::OBJ_NEW));
		$this->assertCount(2, $this->objectManager->getObject(ObjectManager::OBJ_MANAGED));
		$this->assertCount(3, $this->objectManager->getObject(ObjectManager::OBJ_REMOVED));
	}

	public function test_clear(){
		$object1 = new ObjectMapping();
		$this->objectManager->addObject($object1);
		$object2 = new ObjectMapping();
		$this->objectManager->addObject($object2, ObjectManager::OBJ_MANAGED);
		$object3 = new ObjectMapping();
		$this->objectManager->addObject($object3, ObjectManager::OBJ_MANAGED);
		$object4 = new ObjectMapping();
		$this->objectManager->addObject($object4, ObjectManager::OBJ_REMOVED);
		$object5 = new ObjectMapping();
		$this->objectManager->addObject($object5, ObjectManager::OBJ_REMOVED);
		$object6 = new ObjectMapping();
		$this->objectManager->addObject($object6, ObjectManager::OBJ_REMOVED);

		$this->assertCount(6, $this->getPrivatePropValue($this->objectManager, "objects"));
		$this->objectManager->clear();
		$this->assertCount(0, $this->getPrivatePropValue($this->objectManager, "objects"));

	}

	private function getPrivatePropValue($object, $propertyName){
		$reflProp = new \ReflectionProperty($object, $propertyName);
		$reflProp->setAccessible(true);
		return $reflProp->getValue($object);
	}

}