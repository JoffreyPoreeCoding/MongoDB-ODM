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
use JPC\MongoDB\ODM\Hydration\DataTransformer\DataTransformerContainer;

abstract class DataTransformerHydrator extends AbstractHydrator
{
    public function __construct(
        ClassMetadataFactory $classMetadataFactory,
        private DataTransformerContainer $dataTransformerContainer
    ) {
        parent::__construct($classMetadataFactory);
    }

    protected function transformValue(PropertyMetadata $field, mixed $value): mixed
    {
        return $this->dataTransformerContainer->transform($field, $value);
    }

    protected function reverseTransformValue(PropertyMetadata $field, mixed $value): mixed
    {
        return $this->dataTransformerContainer->reverseTransform($field, $value);
    }
}
