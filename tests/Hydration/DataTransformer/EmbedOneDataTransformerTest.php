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
use JPC\MongoDB\ODM\Hydration\DataTransformer\EmbedOneDataTransformer;
use JPC\MongoDB\ODM\Hydration\HydratorFactory;
use JPC\MongoDB\ODM\Hydration\HydratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class EmbedOneDataTransformerTest extends TestCase
{
    private MockObject & HydratorFactory $hydratorFactoryMock;

    private EmbedOneDataTransformer $dataTransformer;

    protected function setUp(): void
    {
        $this->hydratorFactoryMock = $this->createMock(HydratorFactory::class);
        $this->dataTransformer     = new EmbedOneDataTransformer($this->hydratorFactoryMock);
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
            'multiple' => false,
            'expected' => true,
        ];

        yield 'notEmbedded' => [
            'embbeded' => false,
            'multiple' => false,
            'expected' => false,
        ];

        yield 'multiple' => [
            'embbeded' => true,
            'multiple' => true,
            'expected' => false,
        ];
    }

    public function test_transform(): void
    {
        $data = [
            'field_1' => 'value',
            'field_2' => 'value',
        ];

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn(stdClass::class);

        $hydratorMock = $this->createMock(HydratorInterface::class);
        $hydratorMock->expects($this->once())->method('hydrate')->with($this->isInstanceOf(stdClass::class), $data);

        $this->hydratorFactoryMock->expects($this->once())->method('getHydrator')->with(stdClass::class)->willReturn($hydratorMock);

        $this->assertInstanceOf(stdClass::class, $this->dataTransformer->transform($propertyMetadataMock, $data));
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

    public function test_reverseTransform(): void
    {
        $data = new stdClass();

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn(stdClass::class);

        $hydratorMock = $this->createMock(HydratorInterface::class);
        $hydratorMock->expects($this->once())->method('dehydrate')->with($this->isInstanceOf(stdClass::class))->willReturn(['field_1' => 'value']);

        $this->hydratorFactoryMock->expects($this->once())->method('getHydrator')->with(stdClass::class)->willReturn($hydratorMock);

        $this->assertEquals(['field_1' => 'value'], $this->dataTransformer->reverseTransform($propertyMetadataMock, $data));
    }

    public function test_reverseTransform_valueIsNotSpecifiedEmbeddedClass(): void
    {
        $data = 'string';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);
        $propertyMetadataMock->expects($this->once())->method('getEmbeddedClass')->willReturn('EmbeddedClass');
        $propertyMetadataMock->expects($this->once())->method('getName')->willReturn('field1');

        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage('Can not dehydrate value from property "field1" because it is not an instance of "EmbeddedClass"');

        $this->dataTransformer->reverseTransform($propertyMetadataMock, $data);
    }
}
