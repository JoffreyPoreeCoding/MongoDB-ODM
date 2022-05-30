<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ClassMetadata\Attribute;

use Attribute;
use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
class Document implements AttributeInterface
{
    public function __construct(
        private string $collection
    ) {
    }

    public function map(ClassMetadata|PropertyMetadata $metadata): void
    {
        $metadata->setCollection($this->collection);
    }
}