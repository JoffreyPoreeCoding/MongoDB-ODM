<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Configuration;

use JPC\MongoDB\ODM\Hydration\AccessorHydrator;

class Configuration
{
    private string $defaultHydratorClass;

    public function __construct()
    {
        $this->defaultHydratorClass = AccessorHydrator::class;
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
}
