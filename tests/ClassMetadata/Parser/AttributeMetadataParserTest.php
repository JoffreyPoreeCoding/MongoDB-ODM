<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\ClassMetadata\Parser;

use JPC\MongoDB\ODM\ClassMetadata\Attribute\AttributeInterface;
use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\ClassMetadata\Parser\AttributeMetadataParser;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;

class AttributeMetadataParserTest extends TestCase
{
    private AttributeMetadataParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AttributeMetadataParser();
    }

    public function test_parseClassMetadata(): void
    {
        $reflectionMock          = $this->createMock(ReflectionClass::class);
        $metadataMock            = $this->createMock(ClassMetadata::class);
        $reflectionAttributeMock = $this->createMock(ReflectionAttribute::class);
        $attributeInstanceMock   = $this->createMock(AttributeInterface::class);

        $reflectionMock->expects($this->once())->method('getAttributes')->with(AttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF)->willReturn([
            $reflectionAttributeMock,
        ]);
        $reflectionAttributeMock->expects($this->once())->method('newInstance')->willReturn($attributeInstanceMock);
        $attributeInstanceMock->expects($this->once())->method('map')->with($metadataMock);

        $this->parser->parse($reflectionMock, $metadataMock);
    }
}
