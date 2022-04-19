<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\Hydration\DataTransformer;

use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use JPC\MongoDB\ODM\Exception\HydrationException;
use JPC\MongoDB\ODM\Hydration\DataTransformer\EmbedManyDataTransformer;
use JPC\MongoDB\ODM\Hydration\HydratorFactory;
use JPC\MongoDB\ODM\Hydration\HydratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class EmbedManyDataTransformerTest extends TestCase
{
    private MockObject & HydratorFactory $hydratorFactoryMock;

    private EmbedManyDataTransformer $dataTransformer;

    protected function setUp(): void
    {
        $this->hydratorFactoryMock = $this->createMock(HydratorFactory::class);
        $this->dataTransformer     = new EmbedManyDataTransformer($this->hydratorFactoryMock);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function test_supports(bool $embedded, bool $multiple, bool $expected): void
    {
        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->method('isEmbedded')->willReturn($embedded);
        $propertyMetadataMock->method('isMultiple')->willReturn($multiple);

        $this->assertEquals($expected, $this->dataTransformer->supports($propertyMetadataMock));
    }

    public function supportsDataProvider()
    {
        yield 'ok' => [
            'embbeded' => true,
            'multiple' => true,
            'expected' => true,
        ];

        yield 'notEmbedded' => [
            'embbeded' => false,
            'multiple' => true,
            'expected' => false,
        ];

        yield 'notMultiple' => [
            'embbeded' => true,
            'multiple' => false,
            'expected' => false,
        ];
    }

    public function test_transform(): void
    {
        $data = [
            'key_1' => [
                'field_1' => 'value',
                'field_2' => 'value',
            ],
            [
                'field_1' => 'value',
                'field_2' => 'value',
            ],
        ];

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn(stdClass::class);

        $hydratorMock = $this->createMock(HydratorInterface::class);
        $hydratorMock->expects($this->exactly(2))->method('hydrate')->withConsecutive(
            [$this->isInstanceOf(stdClass::class), $data['key_1']],
            [$this->isInstanceOf(stdClass::class), $data[0]],
        );

        $this->hydratorFactoryMock->expects($this->once())->method('getHydrator')->with(stdClass::class)->willReturn($hydratorMock);

        $result = $this->dataTransformer->transform($propertyMetadataMock, $data);
        $this->assertInstanceOf(stdClass::class, $result['key_1']);
        $this->assertInstanceOf(stdClass::class, $result[0]);
    }

    public function test_transform_valueIsNotArray(): void
    {
        $data = 'a string';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not hydrate value into embedded property "field1" because it is not an array value');

        $this->dataTransformer->transform($propertyMetadataMock, $data);
    }

    public function test_transform_innerValueIsNotArray(): void
    {
        $data = [
            'a string',
        ];

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn(stdClass::class);
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');

        $hydratorMock = $this->createMock(HydratorInterface::class);

        $this->hydratorFactoryMock->expects($this->once())->method('getHydrator')->with(stdClass::class)->willReturn($hydratorMock);

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not hydrate value into embedded property "field1" with key "0" because it is not an array value');

        $this->dataTransformer->transform($propertyMetadataMock, $data);
    }

    public function test_reverseTransform(): void
    {
        $data = [
            'key_1' => new stdClass(),
            new stdClass(),
        ];

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn(stdClass::class);

        $hydratorMock = $this->createMock(HydratorInterface::class);
        $hydratorMock->expects($this->exactly(2))->method('dehydrate')->with($this->isInstanceOf(stdClass::class))->willReturnOnConsecutiveCalls(
            ['field_1' => 'value_1'],
            ['field_1' => 'value_2']
        );

        $this->hydratorFactoryMock->expects($this->once())->method('getHydrator')->with(stdClass::class)->willReturn($hydratorMock);

        $this->assertEquals([
            'key_1'    => ['field_1' => 'value_1'],
            ['field_1' => 'value_2'],
        ], $this->dataTransformer->reverseTransform($propertyMetadataMock, $data));
    }

    public function test_reverseTransform_valueIsNotArray(): void
    {
        $data = 'data';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not dehydrate value from property "field1" because it is not an array value');

        $this->dataTransformer->reverseTransform($propertyMetadataMock, $data);
    }

    public function test_reverseTransform_classNotMatch(): void
    {
        $data = [
            'key_1' => new stdClass(),
            new stdClass(),
        ];

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn('EmbeddedClass');
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');

        $hydratorMock = $this->createMock(HydratorInterface::class);
        $this->hydratorFactoryMock->expects($this->once())->method('getHydrator')->with('EmbeddedClass')->willReturn($hydratorMock);

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not dehydrate value with key "key_1" of property "field1" because value is not an instance of "EmbeddedClass"');

        $this->dataTransformer->reverseTransform($propertyMetadataMock, $data);
    }
}
