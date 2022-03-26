<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ClassMetadata;

/**
 * @codeCoverageIgnore
 */
class ClassMetadata
{
    private string $className;

    private string $namespace;

    private string $collection;

    private array $properties;

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
