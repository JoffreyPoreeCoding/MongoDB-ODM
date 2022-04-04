<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\Hydration;

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\ClassMetadata\ClassMetadataFactory;
use JPC\MongoDB\ODM\Configuration\Configuration;
use JPC\MongoDB\ODM\Hydration\AccessorHydrator;
use JPC\MongoDB\ODM\Hydration\HydratorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HydratorFactoryTest extends TestCase
{
    private Configuration & MockObject $configurationMock;

    private HydratorFactory $hydratorFactory;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->hydratorFactory   = new HydratorFactory($this->configurationMock);
    }

    public function test_getHydrator(): void
    {
        $object = new class()
        {
        };

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())->method('getHydratorClass')->willReturn(AccessorHydrator::class);

        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadata);

        $this->configurationMock->expects($this->once())->method('getClassMetadataFactory')->willReturn($classMetadataFactoryMock);

        $hydrator = $this->hydratorFactory->getHydrator($object);
        $this->assertInstanceOf(AccessorHydrator::class, $hydrator);
    }
}
