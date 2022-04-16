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
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use JPC\MongoDB\ODM\Hydration\AbstractHydrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class AbstractHydratorTest extends TestCase
{
    private MockObject & ClassMetadataFactory $classMetadataFactoryMock;

    private MockObject & AbstractHydrator $abstractHydrator;

    protected function setUp(): void
    {
        $this->classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $this->abstractHydrator         = $this->getMockForAbstractClass(AbstractHydrator::class, [$this->classMetadataFactoryMock]);
    }

    public function test_hydrate(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->exactly(2))->method('getField')->withConsecutive(
            ['field_1'],
            ['field_2']
        )->willReturnOnConsecutiveCalls($propertyMetadataMock, null);

        $object = new stdClass();
        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->abstractHydrator->expects($this->once())->method('transformValue')->with($propertyMetadataMock, 'value_1')->willReturn('value_modified');
        $this->abstractHydrator->expects($this->once())->method('setValue')->with($object, $propertyMetadataMock, 'value_modified');

        $this->abstractHydrator->hydrate($object, ['field_1' => 'value_1', 'field_2' => 'value_2']);
    }

    public function test_dehydrate(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getFieldName')->willReturn('field_1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getFields')->willReturn([$propertyMetadataMock]);

        $object = new stdClass();
        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->abstractHydrator->expects($this->once())->method('getValue')->with($object, $propertyMetadataMock)->willReturn('value_got');
        $this->abstractHydrator->expects($this->once())->method('reverseTransformValue')->with($propertyMetadataMock, 'value_got')->willReturn('value_transformed');

        $result = $this->abstractHydrator->dehydrate($object);
        $this->assertEquals(['field_1' => 'value_transformed'], $result);
    }
}
