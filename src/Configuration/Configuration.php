<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Configuration;

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadataFactory;
use JPC\MongoDB\ODM\ClassMetadata\Parser\AttributeMetadataParser;
use JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface;
use JPC\MongoDB\ODM\Exception\Configuration\MisconfigurationException;
use JPC\MongoDB\ODM\Hydration\AccessorHydrator;
use JPC\MongoDB\ODM\Hydration\HydratorFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class Configuration
{
    private CacheInterface $cache;

    private array $metadataParsers;

    private string $defaultHydratorClass;

    private string $classMetadataFactoryClass;

    private ClassMetadataFactory $classMetadataFactory;

    private string $hydratorFactoryClass;

    private HydratorFactory $hydratorFactory;

    public function __construct()
    {
        $this->cache           = new ArrayAdapter();
        $this->metadataParsers = [
            new AttributeMetadataParser(),
        ];
        $this->defaultHydratorClass      = AccessorHydrator::class;
        $this->classMetadataFactoryClass = ClassMetadataFactory::class;
        $this->hydratorFactoryClass      = HydratorFactory::class;
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

    public function getDefaultHydratorClass(): string
    {
        return $this->defaultHydratorClass;
    }

    public function setDefaultHydratorClass($defaultHydratorClass): self
    {
        $this->defaultHydratorClass = $defaultHydratorClass;

        return $this;
    }

    public function getClassMetadataFactoryClass(): string
    {
        return $this->classMetadataFactoryClass;
    }

    public function setClassMetadataFactoryClass($classMetadataFactoryClass): self
    {
        $this->classMetadataFactoryClass = $classMetadataFactoryClass;

        return $this;
    }

    public function getClassMetadataFactory(): ClassMetadataFactory
    {
        if (!isset($this->classMetadataFactory)) {
            $class                      = $this->getClassMetadataFactoryClass();
            $this->classMetadataFactory = new $class($this);
        }

        return $this->classMetadataFactory;
    }

    public function setClassMetadataFactory(ClassMetadataFactory $classMetadataFactory): self
    {
        if (!isset($this->classMetadataFactory)) {
            $this->classMetadataFactory = $classMetadataFactory;
        } else {
            throw new MisconfigurationException('Cannot set ClassMetadataFactory when already initiated', 10101);
        }

        return $this;
    }

    public function getHydratorFactoryClass(): string
    {
        return $this->hydratorFactoryClass;
    }

    public function setHydratorFactoryClass($hydratorFactoryClass): self
    {
        $this->hydratorFactoryClass = $hydratorFactoryClass;

        return $this;
    }

    public function getHydratorFactory(): HydratorFactory
    {
        if (!isset($this->hydratorFactory)) {
            $class                 = $this->getHydratorFactoryClass();
            $this->hydratorFactory = new $class($this);
        }

        return $this->hydratorFactory;
    }

    public function setHydratorFactory(HydratorFactory $hydratorFactory): self
    {
        if (!isset($this->hydratorFactory)) {
            $this->hydratorFactory = $hydratorFactory;
        } else {
            throw new MisconfigurationException('Cannot set HydratorFactory when already initiated', 10101);
        }

        return $this;
    }
}
