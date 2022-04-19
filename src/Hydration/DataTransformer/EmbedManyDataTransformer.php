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

class EmbedManyDataTransformer implements DataTransformerInterface
{
    public function __construct(
        protected HydratorFactory $hydratorFactory
    ) {
    }

    public function supports(PropertyMetadata $propertyMetadata): bool
    {
        return $propertyMetadata->isEmbedded() && $propertyMetadata->isMultiple();
    }

    public function transform(PropertyMetadata $propertyMetadata, mixed $value): mixed
    {
        if (!is_array($value)) {
            throw new HydrationException('Can not hydrate value into embedded property "' . $propertyMetadata->getName() . '" because it is not an array value');
        }

        $class    = $propertyMetadata->getEmbeddedClass();
        $hydrator = $this->hydratorFactory->getHydrator($class);
        $newValue = [];

        foreach ($value as $key => $data) {
            if (!is_array($data)) {
                throw new HydrationException('Can not hydrate value into embedded property "' . $propertyMetadata->getName() . '" with key "' . $key . '" because it is not an array value');
            }
            $object = new $class();
            $hydrator->hydrate($object, $data);

            $newValue[$key] = $object;
        }

        return $newValue;
    }

    public function reverseTransform(PropertyMetadata $propertyMetadata, mixed $value): mixed
    {
        if (!is_array($value)) {
            throw new HydrationException('Can not dehydrate value from property "' . $propertyMetadata->getName() . '" because it is not an array value');
        }
        $class    = $propertyMetadata->getEmbeddedClass();
        $hydrator = $this->hydratorFactory->getHydrator($class);

        $data = [];

        foreach ($value as $key => $object) {
            if (!is_object($object) || !is_a($object, $class)) {
                throw new HydrationException('Can not dehydrate value with key "' . $key . '" of property "' . $propertyMetadata->getName() . '" because value is not an instance of "' . $class . '"');
            }

            $data[$key] = $hydrator->dehydrate($object);
        }

        return $data;
    }
}
