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

interface DataTransformerInterface
{
    public function supports(PropertyMetadata $propertyMetadata): bool;

    public function transform(PropertyMetadata $propertyMetadata, mixed $value): mixed;

    public function reverseTransform(PropertyMetadata $propertyMetadata, mixed $value): mixed;
}