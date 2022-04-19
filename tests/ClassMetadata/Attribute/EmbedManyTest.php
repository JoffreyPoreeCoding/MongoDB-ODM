<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\ClassMetadata\Attribute;

use JPC\MongoDB\ODM\ClassMetadata\Attribute\EmbedMany;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use PHPUnit\Framework\TestCase;

class EmbedManyTest extends TestCase
{
    public function test_map(): void
    {
        $propertyMetadata = $this->createMock(PropertyMetadata::class);
        $propertyMetadata->expects($this->once())->method('setEmbedded')->with(true);
        $propertyMetadata->expects($this->once())->method('setMultiple')->with(true);
        $propertyMetadata->expects($this->once())->method('setEmbeddedClass')->with('Class');

        $field = new EmbedMany('Class');
        $field->map($propertyMetadata);
    }
}
