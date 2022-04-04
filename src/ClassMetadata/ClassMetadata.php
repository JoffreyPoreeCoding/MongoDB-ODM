<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ClassMetadata;

use JPC\MongoDB\ODM\Configuration\Configuration;

/**
 * @codeCoverageIgnore
 */
class ClassMetadata
{
    private string $className;

    private string $namespace;

    private string $collection;

    private string $hydratorClass;

    private array $properties;

    public function __construct(Configuration $configuration)
    {
        $this->hydratorClass = $configuration->getDefaultHydratorClass();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace($namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function setCollection($collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    public function getHydratorClass(): string
    {
        return $this->hydratorClass;
    }

    public function setHydratorClass(string $hydratorClass)
    {
        $this->hydratorClass = $hydratorClass;

        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(PropertyMetadata $property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }
}
