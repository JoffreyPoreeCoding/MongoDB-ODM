<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration;

interface HydratorInterface
{
    /**
     * Hydrate object with provided data.
     */
    public function hydrate(object $object, array $data): void;

    /**
     * Extract data from object.
     */
    public function dehydrate(object $object): array;
}
