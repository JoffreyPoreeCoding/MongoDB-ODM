<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ObjectManagement;

use WeakMap;

class WeakObjectManager extends ObjectManager
{
    protected iterable $objectStates;

    public function __construct()
    {
        $this->objectStates = new WeakMap();
    }

    public function getObjectId(object $object): mixed
    {
        return $object;
    }
}
