<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration;

use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use JPC\MongoDB\ODM\Exception\HydrationException;

class AccessorHydrator extends DataTransformerHydrator
{
    protected function setValue(object $object, PropertyMetadata $propertyMetadata, mixed $value): void
    {
        $setter = 'set' . ucfirst($propertyMetadata->getName());

        if (!method_exists($object, $setter)) {
            throw new HydrationException('Can not hydrate property "' . $propertyMetadata->getName() . '" because method "' . $setter . '" not found in class "' . $object::class . '"');
        }

        $object->{$setter}($value);
    }

    protected function getValue(object $object, PropertyMetadata $propertyMetadata): mixed
    {
        $getter = 'get' . ucfirst($propertyMetadata->getName());

        if (!method_exists($object, $getter)) {
            throw new HydrationException('Can not dehydrate property "' . $propertyMetadata->getName() . '" because method "' . $getter . '" not found in class "' . $object::class . '"');
        }

        return $object->{$getter}();
    }
}
