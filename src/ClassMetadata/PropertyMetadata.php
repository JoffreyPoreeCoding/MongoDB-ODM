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
class PropertyMetadata
{
    private string $name;

    private string $fieldName;

    private bool $embedded = false;

    private bool $multiple = false;

    private ?string $embeddedClass = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName ?? null;
    }

    public function setFieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function isEmbedded(): bool
    {
        return $this->embedded;
    }

    public function setEmbedded(bool $embedded): self
    {
        $this->embedded = $embedded;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getEmbeddedClass(): ?string
    {
        return $this->embeddedClass;
    }

    public function setEmbeddedClass(string $embeddedClass): self
    {
        $this->embeddedClass = $embeddedClass;

        return $this;
    }
}
