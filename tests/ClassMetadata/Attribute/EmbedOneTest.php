<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use JPC\MongoDB\ODM\ClassMetadata\Attribute\EmbedOne;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use PHPUnit\Framework\TestCase;

class EmbedOneTest extends TestCase
{
    public function test_map(): void
    {
        $propertyMetadata = $this->createMock(PropertyMetadata::class);
        $propertyMetadata->expects($this->once())->method('setEmbedded')->with(true);
        $propertyMetadata->expects($this->once())->method('setEmbeddedClass')->with('Class');

        $field = new EmbedOne('Class');
        $field->map($propertyMetadata);
    }
}
