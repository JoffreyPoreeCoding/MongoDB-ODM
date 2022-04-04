<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use JPC\MongoDB\ODM\Hydration\AccessorHydrator;
use PHPUnit\Framework\TestCase;

class AccessorHydratorTest extends TestCase
{
    private AccessorHydrator $hydrator;

    protected function __setUp(): void
    {
        $this->hydrator = new AccessorHydrator();
    }

    public function test_hydrate_basicField(): void
    {
        // $classMetadataMock = $this->createMock(ClassMetadata::class);
        // $classMetadataMock->expects($this->once())->method('getProp')
        // $propertyMetadata = $this->createMock(PropertyMetadata::class);
    }
}
