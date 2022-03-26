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

    private string $field;

    private bool $embedded;

    private bool $multiple;

    private string $embeddedClass;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getEmbedded(): bool
    {
        return $this->embedded;
    }

    public function setEmbedded(bool $embedded): self
    {
        $this->embedded = $embedded;

        return $this;
    }

    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getEmbeddedClass(): string
    {
        return $this->embeddedClass;
    }

    public function setEmbeddedClass(string $embeddedClass): self
    {
        $this->embeddedClass = $embeddedClass;

        return $this;
    }
}
