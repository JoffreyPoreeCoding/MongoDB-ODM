<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\ClassMetadata\Attribute;

use JPC\MongoDB\ODM\ClassMetadata\Attribute\Field;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function test_map(): void
    {
        $propertyMetadata = $this->createMock(PropertyMetadata::class);
        $propertyMetadata->expects($this->once())->method('setFieldName')->with('field_name');

        $field = new Field('field_name');
        $field->map($propertyMetadata);
    }
}
