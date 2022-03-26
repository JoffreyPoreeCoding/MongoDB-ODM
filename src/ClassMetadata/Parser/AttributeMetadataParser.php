<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ClassMetadata\Parser;

use JPC\MongoDB\ODM\ClassMetadata\Attribute\AttributeInterface;
use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

class AttributeMetadataParser implements ClassMetadataParserInterface
{
    final public function parse(ReflectionClass|ReflectionProperty $class, ClassMetadata|PropertyMetadata $metadata): bool
    {
        $attributes = $class->getAttributes(AttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            $attribute = $attribute->newInstance();
            $attribute->map($metadata);
        }

        return true;
    }
}
