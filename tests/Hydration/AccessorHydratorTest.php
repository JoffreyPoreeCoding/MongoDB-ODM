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
use JPC\MongoDB\ODM\Exception\HydrationException;
use JPC\MongoDB\ODM\Hydration\AccessorHydrator;
use JPC\MongoDB\ODM\Hydration\DataTransformer\DataTransformerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class AccessorHydratorTest extends TestCase
{
    private MockObject & ClassMetadataFactory $classMetadataFactoryMock;

    private MockObject & DataTransformerContainer $dataTransformerContainerMock;

    private AccessorHydrator $accessorHydrator;

    protected function setUp(): void
    {
        $this->classMetadataFactoryMock     = $this->createMock(ClassMetadataFactory::class);
        $this->dataTransformerContainerMock = $this->createMock(DataTransformerContainer::class);
        $this->accessorHydrator             = new AccessorHydrator($this->classMetadataFactoryMock, $this->dataTransformerContainerMock);
    }

    public function test_hydrate(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->exactly(2))->method('getField')->withConsecutive(
            ['field_1'],
            ['field_2']
        )->willReturnOnConsecutiveCalls($propertyMetadataMock, null);

        $object = $this->getMockBuilder(stdClass::class)->addMethods(['setField1'])->getMock();
        $object->expects($this->once())->method('setField1')->with('value_modified');

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->dataTransformerContainerMock->expects($this->once())->method('transform')->with($propertyMetadataMock, 'value_1')->willReturn('value_modified');

        $this->accessorHydrator->hydrate($object, ['field_1' => 'value_1', 'field_2' => 'value_2']);
    }

    public function test_hydrate_setterNotFound(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->exactly(2))->method('getName')->willReturn('field1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getField')->withConsecutive(
            ['field_1'],
        )->willReturnOnConsecutiveCalls($propertyMetadataMock, null);

        $object = $this->createMock(stdClass::class);

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->dataTransformerContainerMock->expects($this->once())->method('transform')->with($propertyMetadataMock, 'value_1')->willReturn('value_modified');

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not hydrate property "field1" because method "setField1" not found in class "' . $object::class . '"');
        $this->expectExceptionCode(11000);

        $this->accessorHydrator->hydrate($object, ['field_1' => 'value_1']);
    }

    public function test_dehydrate(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');
        $propertyMetadataMock->expects($this->once())->method('getFieldName')->willReturn('field_1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getFields')->willReturn([$propertyMetadataMock]);

        $object = $this->getMockBuilder(stdClass::class)->addMethods(['getField1'])->getMock();
        $object->expects($this->once())->method('getField1')->willReturn('value_got');

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->dataTransformerContainerMock->expects($this->once())->method('reverseTransform')->with($propertyMetadataMock, 'value_got')->willReturn('value_transformed');

        $result = $this->accessorHydrator->dehydrate($object);
        $this->assertEquals(['field_1' => 'value_transformed'], $result);
    }

    public function test_dehydrate_getterNotFound(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->exactly(2))->method('getName')->willReturn('field1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getFields')->willReturn([$propertyMetadataMock]);

        $object = $this->createMock(stdClass::class);

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not dehydrate property "field1" because method "getField1" not found in class "' . $object::class . '"');
        $this->expectExceptionCode(11000);

        $result = $this->accessorHydrator->dehydrate($object);
    }
}
