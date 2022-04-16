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
use JPC\MongoDB\ODM\Hydration\ReflectionHydrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReflectionHydratorTest extends TestCase
{
    private MockObject & ClassMetadataFactory $classMetadataFactoryMock;

    private MockObject & DataTransformerContainer $dataTransformerContainerMock;

    private ReflectionHydrator $reflectionHydrator;

    protected function setUp(): void
    {
        $this->classMetadataFactoryMock     = $this->createMock(ClassMetadataFactory::class);
        $this->dataTransformerContainerMock = $this->createMock(DataTransformerContainer::class);
        $this->reflectionHydrator           = new ReflectionHydrator($this->classMetadataFactoryMock, $this->dataTransformerContainerMock);
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

        $object = new class()
        {
            private string $field1;

            public function getField1()
            {
                return $this->field1;
            }
        };

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->dataTransformerContainerMock->expects($this->once())->method('transform')->with($propertyMetadataMock, 'value_1')->willReturn('value_modified');

        $this->reflectionHydrator->hydrate($object, ['field_1' => 'value_1', 'field_2' => 'value_2']);
    }

    public function test_dehydrate(): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');
        $propertyMetadataMock->expects($this->once())->method('getFieldName')->willReturn('field_1');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('getFields')->willReturn([$propertyMetadataMock]);

        $object = new class()
        {
            private string $field1 = 'value_got';
        };

        $this->classMetadataFactoryMock->expects($this->once())->method('getMetadata')->with($object::class)->willReturn($classMetadataMock);

        $this->dataTransformerContainerMock->expects($this->once())->method('reverseTransform')->with($propertyMetadataMock, 'value_got')->willReturn('value_transformed');

        $result = $this->reflectionHydrator->dehydrate($object);
        $this->assertEquals(['field_1' => 'value_transformed'], $result);
    }
}
