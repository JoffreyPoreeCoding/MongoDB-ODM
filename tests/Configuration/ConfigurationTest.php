<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\Configuration;

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadataFactory;
use JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface;
use JPC\MongoDB\ODM\Configuration\Configuration;
use JPC\MongoDB\ODM\Exception\Configuration\MisconfigurationException;
use JPC\MongoDB\ODM\Hydration\AccessorHydrator;
use JPC\MongoDB\ODM\Hydration\HydratorFactory;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class ConfigurationTest extends TestCase
{
    public function test_getDefaultValues(): void
    {
        $configuration = new Configuration();

        $this->assertInstanceOf(ArrayAdapter::class, $configuration->getCache());
        $this->assertIsArray($configuration->getMetadataParsers());
        $this->assertCount(1, $configuration->getMetadataParsers());
        $this->assertEquals(ClassMetadataFactory::class, $configuration->getClassMetadataFactoryClass());
        $this->assertEquals(HydratorFactory::class, $configuration->getHydratorFactoryClass());
        $this->assertEquals(AccessorHydrator::class, $configuration->getDefaultHydratorClass());
    }

    public function test_setAndGet(): void
    {
        $configuration = new Configuration();

        $cache = $this->createMock(CacheInterface::class);
        $configuration->setCache($cache);
        $this->assertSame($cache, $configuration->getCache());

        $metadataParsers = [$this->createMock(ClassMetadataParserInterface::class)];
        $configuration->setMetadataParsers($metadataParsers);
        $this->assertSame($metadataParsers, $configuration->getMetadataParsers());

        $defaultHydratorClass = 'HydratorClass';
        $configuration->setDefaultHydratorClass($defaultHydratorClass);
        $this->assertSame($defaultHydratorClass, $configuration->getDefaultHydratorClass());

        $classMetadataFactoryClass = 'ClassMetadataFactory';
        $configuration->setClassMetadataFactoryClass($classMetadataFactoryClass);
        $this->assertSame($classMetadataFactoryClass, $configuration->getClassMetadataFactoryClass());

        $classMetadataFactory = new class($configuration) extends ClassMetadataFactory
        {
        };
        $configuration->setClassMetadataFactory($classMetadataFactory);
        $this->assertSame($classMetadataFactory, $configuration->getClassMetadataFactory());

        $hydratorFactoryClass = 'HydratorFactory';
        $configuration->setHydratorFactoryClass($hydratorFactoryClass);
        $this->assertSame($hydratorFactoryClass, $configuration->getHydratorFactoryClass());

        $hydratorFactory = new class($configuration) extends HydratorFactory
        {
        };
        $configuration->setHydratorFactory($hydratorFactory);
        $this->assertSame($hydratorFactory, $configuration->getHydratorFactory());
    }

    public function test_setMetadataParsers_badValue(): void
    {
        $configuration = new Configuration();

        $metadataParsers = [new stdClass()];

        $this->expectException(MisconfigurationException::class);
        $this->expectExceptionMessage('Metadata parser must be an instance of "JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface", "stdClass" given.');
        $configuration->setMetadataParsers($metadataParsers);
    }

    public function test_getClassMetadataFactory_notInitiated(): void
    {
        $configuration = new Configuration();

        $classMetadataFactory = new class($configuration) extends ClassMetadataFactory
        {
        };

        $configuration->setClassMetadataFactoryClass($classMetadataFactory::class);

        $this->assertInstanceOf($classMetadataFactory::class, $configuration->getClassMetadataFactory());
    }

    public function test_setClassMetadataFactory_alreadyInitialized(): void
    {
        $configuration = new Configuration();

        $classMetadataFactory = new class($configuration) extends ClassMetadataFactory
        {
        };
        $configuration->setClassMetadataFactory($classMetadataFactory);

        $this->expectException(MisconfigurationException::class);
        $this->expectExceptionCode(10101);
        $configuration->setClassMetadataFactory($classMetadataFactory);
    }

    public function test_getHydratorFactory_notInitiated(): void
    {
        $configuration = new Configuration();

        $hydratorFactory = new class($configuration) extends HydratorFactory
        {
        };

        $configuration->setHydratorFactoryClass($hydratorFactory::class);

        $this->assertInstanceOf($hydratorFactory::class, $configuration->getHydratorFactory());
    }

    public function test_setHydratorFactory_alreadyInitialized(): void
    {
        $configuration = new Configuration();

        $hydratorFactory = new class($configuration) extends HydratorFactory
        {
        };
        $configuration->setHydratorFactory($hydratorFactory);

        $this->expectException(MisconfigurationException::class);
        $this->expectExceptionCode(10101);
        $configuration->setHydratorFactory($hydratorFactory);
    }
}
