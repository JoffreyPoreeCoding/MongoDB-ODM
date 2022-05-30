<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration\DataTransformer;

use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use JPC\MongoDB\ODM\Exception\HydrationException;
use JPC\MongoDB\ODM\Hydration\HydratorFactory;

class EmbedOneDataTransformer implements DataTransformerInterface
{
    public function __construct(
        protected HydratorFactory $hydratorFactory
    ) {
    }

    public function supports(PropertyMetadata $propertyMetadata): bool
    {
        return $propertyMetadata->isEmbedded() && !$propertyMetadata->isMultiple();
    }

    public function transform(PropertyMetadata $propertyMetadata, mixed $value): mixed
    {
        if (!is_array($value)) {
            throw new HydrationException('Can not hydrate value into embedded property "' . $propertyMetadata->getName() . '" because it is not an array value');
        }

        $class = $propertyMetadata->getEmbeddedClass();

        $object   = new $class();
        $hydrator = $this->hydratorFactory->getHydrator($object::class);
        $hydrator->hydrate($object, $value);

        return $object;
    }

    public function reverseTransform(PropertyMetadata $propertyMetadata, mixed $value): mixed
    {
        if (null === $value) {
            return $value;
        }

        $class = $propertyMetadata->getEmbeddedClass();

        if (!is_object($value) || !is_a($value, $class)) {
            throw new HydrationException('Can not dehydrate value from property "' . $propertyMetadata->getName() . '" because it is not an instance of "' . $class . '"');
        }

        $hydrator = $this->hydratorFactory->getHydrator($value::class);

        return $hydrator->dehydrate($value);
    }
}
