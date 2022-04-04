<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\ClassMetadata;

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\ClassMetadata\ClassMetadataFactory;
use JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface;
use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;
use JPC\MongoDB\ODM\Configuration\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Contracts\Cache\CacheInterface;

class ClassMetadataFactoryTest extends TestCase
{
    private Configuration & MockObject $configurationMock;

    private CacheInterface & MockObject $cacheInterfaceMock;

    private ClassMetadataParserInterface & MockObject $classMetadataParserMock;

    private ClassMetadataFactory $classMetadataFactory;

    protected function setUp(): void
    {
        $this->cacheInterfaceMock = $this->createMock(CacheInterface::class);

        $this->classMetadataParserMock = $this->createMock(ClassMetadataParserInterface::class);

        $this->configurationMock = $this->createMock(Configuration::class);
        $this->configurationMock->method('getCache')->willReturn($this->cacheInterfaceMock);
        $this->configurationMock->method('getMetadataParsers')->willReturn([
            $this->classMetadataParserMock,
        ]);

        $this->classMetadataFactory = new ClassMetadataFactory($this->configurationMock);
    }

    public function test_getMetadata(): void
    {
        $classMetadataMock = $this->createMock(ClassMetadata::class);

        $className = preg_replace("~[\{\}\(\)/\\\\@:]~", '_', TestMetadataClass::class);
        $this->cacheInterfaceMock->expects($this->once())->method('get')->with($className, $this->isType('callable'))->willReturn($classMetadataMock);

        $result = $this->classMetadataFactory->getMetadata(TestMetadataClass::class);

        $this->assertSame($classMetadataMock, $result);
    }

    public function test_createMetadata(): void
    {
        $this->classMetadataParserMock->expects($this->exactly(3))->method('parse')->withConsecutive(
            [
                $this->callback(static fn (ReflectionClass $ref) => $ref->getName() == TestMetadataClass::class),
                $this->isInstanceOf(ClassMetadata::class),
            ],
            [
                $this->callback(static fn (ReflectionProperty $ref) => $ref->getName() == 'propertyOne'),
                $this->isInstanceOf(PropertyMetadata::class),
            ],
            [
                $this->callback(static fn (ReflectionProperty $ref) => $ref->getName() == 'propertyTwo'),
                $this->isInstanceOf(PropertyMetadata::class),
            ],
        )->willReturn(true);

        $result = $this->classMetadataFactory->createMetadata(TestMetadataClass::class);

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertInstanceOf(PropertyMetadata::class, $result->getProperties()[0]);
        $this->assertEquals('propertyOne', $result->getProperties()[0]->getName());
        $this->assertInstanceOf(PropertyMetadata::class, $result->getProperties()[1]);
        $this->assertEquals('propertyTwo', $result->getProperties()[1]->getName());

        $this->assertEquals(TestMetadataClass::class, $result->getClassName());
        $this->assertEquals('JPC\MongoDB\ODM\Tests\ClassMetadata', $result->getNamespace());
    }
}

class TestMetadataClass
{
    private $propertyOne;

    private $propertyTwo;
}
