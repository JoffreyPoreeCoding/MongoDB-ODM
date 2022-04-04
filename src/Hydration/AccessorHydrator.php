<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration;

class AccessorHydrator implements HydratorInterface
{
    public function hydrate(mixed $object, array $data): void
    {
    }

    /**
     * @return AccessorHydrator[]
     */
    public function dehydrate(mixed $object): array
    {
        return [];
    }
}
