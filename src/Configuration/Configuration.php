<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Configuration;

use JPC\MongoDB\ODM\ClassMetadata\Parser\AttributeMetadataParser;
use JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface;
use JPC\MongoDB\ODM\Exception\Configuration\MisconfigurationException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class Configuration
{
    private CacheInterface $cache;

    private array $metadataParsers;

    private string $classMetadataFactoryClass;

    public function __construct()
    {
        $this->cache           = new ArrayAdapter();
        $this->metadataParsers = [
            new AttributeMetadataParser(),
        ];
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    public function getMetadataParsers(): array
    {
        return $this->metadataParsers;
    }

    public function setMetadataParsers(array $metadataParsers): self
    {
        foreach ($metadataParsers as $parser) {
            if (!$parser instanceof ClassMetadataParserInterface) {
                throw new MisconfigurationException('Metadata parser must be an instance of "' . ClassMetadataParserInterface::class . '", "' . $parser::class . '" given.');
            }
        }
        $this->metadataParsers = $metadataParsers;

        return $this;
    }

    public function getClassMetadataFactoryClass(): string
    {
        return $this->classMetadataFactoryClass;
    }

    public function setClassMetadataFactoryClass(string $classMetadataFactoryClass): self
    {
        $this->classMetadataFactoryClass = $classMetadataFactoryClass;

        return $this;
    }
}
