<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ObjectManagement;

interface ObjectManagerInterface
{
    public function add(object $object): self;

    public function set(object $object, State $state): self;

    public function delete(object $object): self;

    public function get(object $object): State;

    public function getObjectId(object $object): mixed;
}
