<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\ObjectManagement;

use JPC\MongoDB\ODM\Exception\ObjectManagementException;
use JPC\MongoDB\ODM\ObjectManagement\ObjectManager;
use JPC\MongoDB\ODM\ObjectManagement\State;
use PHPUnit\Framework\TestCase;
use stdClass;

class ObjectManagerTest extends TestCase
{
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager();
    }

    public function test_add(): void
    {
        $object = new stdClass();
        $this->objectManager->add($object);

        $this->assertEquals(State::NEW, $this->objectManager->get($object));
    }

    public function test_add_twice(): void
    {
        $object = new stdClass();
        $this->objectManager->add($object);
        $this->objectManager->add($object);

        $this->assertEquals(State::NEW, $this->objectManager->get($object));
    }

    /**
     * @dataProvider addAlreadyManagedDataProvider
     */
    public function test_add_alreadyManaged(State $state): void
    {
        $object = new stdClass();
        $this->objectManager->set($object, $state);

        $this->expectException(ObjectManagementException::class);
        $this->expectExceptionMessage('Can\'t add object because is state is already "' . $state->name . '"');
        $this->expectExceptionCode(12000);

        $this->objectManager->add($object);

        $this->assertEquals(State::MANAGED, $this->objectManager->get($object));
    }

    public function addAlreadyManagedDataProvider()
    {
        return [
            [
                State::MANAGED,
            ],
            [
                State::DELETED,
            ],
        ];
    }

    public function test_delete(): void
    {
        $object = new stdClass();
        $this->objectManager->add($object);

        $this->assertEquals(State::NEW, $this->objectManager->get($object));

        $this->objectManager->delete($object);

        $this->assertEquals(State::UNKNOWN, $this->objectManager->get($object));
    }

    public function test_getObjectId(): void
    {
        $object = new stdClass();
        $this->assertEquals(spl_object_id($object), $this->objectManager->getObjectId($object));
    }
}
