<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration;

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadataFactory;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;

abstract class AbstractHydrator implements HydratorInterface
{
    public function __construct(
        private ClassMetadataFactory $classMetadataFactory
    ) {
    }

    /**
     * Hydrate object with provided data.
     */
    final public function hydrate(object $object, array $data): void
    {
        $classMetadata = $this->classMetadataFactory->getMetadata($object::class);

        foreach ($data as $fieldName => $value) {
            $field = $classMetadata->getField($fieldName);

            if (!isset($field)) {
                continue;
            }

            $value = $this->transformValue($field, $value);

            $this->setValue($object, $field, $value);
        }
    }

    /**
     * Extract data from object.
     */
    final public function dehydrate(object $object): array
    {
        $classMetadata = $this->classMetadataFactory->getMetadata($object::class);

        $data = [];

        foreach ($classMetadata->getFields() as $field) {
            $value = $this->getValue($object, $field);

            $value = $this->reverseTransformValue($field, $value);

            $data[$field->getFieldName()] = $value;
        }

        return $data;
    }

    abstract protected function transformValue(PropertyMetadata $field, mixed $value): mixed;

    abstract protected function reverseTransformValue(PropertyMetadata $field, mixed $value): mixed;

    abstract protected function setValue(object $object, PropertyMetadata $propertyMetadata, mixed $value): void;

    abstract protected function getValue(object $object, PropertyMetadata $propertyMetadata): mixed;
}
