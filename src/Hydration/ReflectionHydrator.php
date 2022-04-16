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
use ReflectionProperty;

class ReflectionHydrator extends DataTransformerHydrator
{
    protected function setValue(object $object, PropertyMetadata $propertyMetadata, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($object, $propertyMetadata->getName());
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
        $reflectionProperty->setAccessible(false);
    }

    protected function getValue(object $object, PropertyMetadata $propertyMetadata): mixed
    {
        $reflectionProperty = new ReflectionProperty($object, $propertyMetadata->getName());
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);
        $reflectionProperty->setAccessible(false);

        return $value;
    }
}
