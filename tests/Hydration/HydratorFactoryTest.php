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
use JPC\MongoDB\ODM\Hydration\HydratorFactory;
use JPC\MongoDB\ODM\Hydration\HydratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class HydratorFactoryTest extends TestCase
{
    private MockObject & ClassMetadataFactory $classMetadataFactoryMock;

    private MockObject & HydratorInterface $hydratorMock;

    private HydratorFactory $hydratorFactory;

    protected function setUp(): void
    {
        $this->classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $this->hydratorMock             = $this->createMock(HydratorInterface::class);

        $this->hydratorFactory = new HydratorFactory($this->classMetadataFactoryMock, ['ValidHydrator' => $this->hydratorMock]);
    }

    public function test_getHydrator_withClassName(): void
    {
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getHydratorClass')->willReturn('ValidHydrator');

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with(stdClass::class)->willReturn($classMetadataMock);

        $this->assertSame($this->hydratorMock, $this->hydratorFactory->getHydrator(stdClass::class));
    }

    public function test_getHydrator_withObject(): void
    {
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getHydratorClass')->willReturn('ValidHydrator');

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with(stdClass::class)->willReturn($classMetadataMock);

        $object = new stdClass();
        $this->assertSame($this->hydratorMock, $this->hydratorFactory->getHydrator($object));
    }
}
