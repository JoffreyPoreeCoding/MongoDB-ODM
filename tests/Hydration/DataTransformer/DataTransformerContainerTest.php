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
use JPC\MongoDB\ODM\Hydration\DataTransformer\DataTransformerContainer;
use JPC\MongoDB\ODM\Hydration\DataTransformer\DataTransformerInterface;
use PHPUnit\Framework\TestCase;

class DataTransformerContainerTest extends TestCase
{
    private iterable $dataTransformerMocks;

    private DataTransformerContainer $dataTransformerContainer;

    protected function setUp(): void
    {
        $this->dataTransformerMocks = [
            $this->createMock(DataTransformerInterface::class),
            $this->createMock(DataTransformerInterface::class),
        ];

        $this->dataTransformerContainer = new DataTransformerContainer($this->dataTransformerMocks);
    }

    public function test_transform_noSupport(): void
    {
        $value = 'a';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);

        foreach ($this->dataTransformerMocks as $transformerMock) {
            $transformerMock->expects($this->once())->method('supports')->with($propertyMetadataMock)->willReturn(false);
        }

        $this->assertEquals('a', $this->dataTransformerContainer->transform($propertyMetadataMock, $value));
    }

    public function test_transform_firstSupport(): void
    {
        $value = 'a';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);

        $this->dataTransformerMocks[0]->expects($this->once())->method('supports')->with($propertyMetadataMock)->willReturn(true);
        $this->dataTransformerMocks[0]->expects($this->once())->method('transform')->with($propertyMetadataMock, $value)->willReturn('b');

        $this->dataTransformerMocks[1]->expects($this->never())->method('supports');
        $this->dataTransformerMocks[1]->expects($this->never())->method('transform');

        $this->assertEquals('b', $this->dataTransformerContainer->transform($propertyMetadataMock, $value));
    }

    public function test_reverseTransform_noSupport(): void
    {
        $value = 'a';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);

        foreach ($this->dataTransformerMocks as $transformerMock) {
            $transformerMock->expects($this->once())->method('supports')->with($propertyMetadataMock)->willReturn(false);
        }

        $this->assertEquals('a', $this->dataTransformerContainer->reverseTransform($propertyMetadataMock, $value));
    }

    public function test_reverseTransform_firstSupport(): void
    {
        $value = 'a';

        $propertyMetadataMock = $this->createMock(PropertyMetadata::class);

        $this->dataTransformerMocks[0]->expects($this->once())->method('supports')->with($propertyMetadataMock)->willReturn(true);
        $this->dataTransformerMocks[0]->expects($this->once())->method('reverseTransform')->with($propertyMetadataMock, $value)->willReturn('b');

        $this->dataTransformerMocks[1]->expects($this->never())->method('supports');
        $this->dataTransformerMocks[1]->expects($this->never())->method('reverseTransform');

        $this->assertEquals('b', $this->dataTransformerContainer->reverseTransform($propertyMetadataMock, $value));
    }
}
