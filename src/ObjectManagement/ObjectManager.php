<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ObjectManagement;

use JPC\MongoDB\ODM\Exception\ObjectManagementException;

class ObjectManager implements ObjectManagerInterface
{
    protected iterable $objectStates = [];

    public function add(object $object): self
    {
        $oid = $this->getObjectId($object);

        if (in_array(($state = $this->objectStates[$oid] ?? null), [State::MANAGED, State::DELETED], true)) {
            throw new ObjectManagementException('Can\'t add object because is state is already "' . $state->name . '"');
        }
        $this->objectStates[$oid] = State::NEW;

        return $this;
    }

    public function set(object $object, State $state): self
    {
        $oid                      = $this->getObjectId($object);
        $this->objectStates[$oid] = $state;

        return $this;
    }

    public function delete(object $object): self
    {
        $oid = $this->getObjectId($object);
        unset($this->objectStates[$oid]);

        return $this;
    }

    public function get(object $object): State
    {
        $oid = $this->getObjectId($object);

        return $this->objectStates[$oid] ?? State::UNKNOWN;
    }

    public function getObjectId(object $object): mixed
    {
        return spl_object_id($object);
    }
}
