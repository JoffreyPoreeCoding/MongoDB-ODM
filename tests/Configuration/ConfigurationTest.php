<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\Configuration;

use JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface;
use JPC\MongoDB\ODM\Configuration\Configuration;
use JPC\MongoDB\ODM\Exception\Configuration\MisconfigurationException;
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
    }

    public function test_setMetadataParsers_badValue(): void
    {
        $configuration = new Configuration();

        $metadataParsers = [new stdClass()];

        $this->expectException(MisconfigurationException::class);
        $this->expectExceptionMessage('Metadata parser must be an instance of "JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface", "stdClass" given.');
        $configuration->setMetadataParsers($metadataParsers);
    }
}
