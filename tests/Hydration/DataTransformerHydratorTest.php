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
use JPC\MongoDB\ODM\Hydration\DataTransformer\DataTransformerContainer;
use JPC\MongoDB\ODM\Hydration\DataTransformerHydrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DataTransformerHydratorTest extends TestCase
{
    private MockObject & ClassMetadataFactory $classMetadataFactoryMock;

    private MockObject & DataTransformerContainer $dataTransformerContainerMock;

    private MockObject & DataTransformerHydrator $dataTransformerHydrator;

    protected function setUp(): void
    {
        $this->classMetadataFactoryMock     = $this->createMock(ClassMetadataFactory::class);
        $this->dataTransformerContainerMock = $this->createMock(DataTransformerContainer::class);
        $this->dataTransformerHydrator      = $this->getMockForAbstractClass(DataTransformerHydrator::class, [$this->classMetadataFactoryMock, $this->dataTransformerContainerMock]);
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

        $this->dataTransformerContainerMock->expects($this->once())->method('transform')->with($propertyMetadataMock, 'value_1')->willReturn('value_modified');

        $this->dataTransformerHydrator->expects($this->once())->method('setValue')->with($object, $propertyMetadataMock, 'value_modified');

        $this->dataTransformerHydrator->hydrate($object, ['field_1' => 'value_1', 'field_2' => 'value_2']);
    }

    public function test_dehydrate(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getFieldName')->willReturn('field_1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getFields')->willReturn([$propertyMetadataMock]);

        $object = new stdClass();
        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->dataTransformerHydrator->expects($this->once())->method('getValue')->with($object, $propertyMetadataMock)->willReturn('value_got');

        $this->dataTransformerContainerMock->expects($this->once())->method('reverseTransform')->with($propertyMetadataMock, 'value_got')->willReturn('value_transformed');

        $result = $this->dataTransformerHydrator->dehydrate($object);
        $this->assertEquals(['field_1' => 'value_transformed'], $result);
    }
}
