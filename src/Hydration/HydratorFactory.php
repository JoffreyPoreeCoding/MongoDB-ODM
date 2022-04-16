<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration;

use JPC\MongoDB\ODM\ClassMetadata\ClassMetadataFactory;

class HydratorFactory
{
    public function __construct(
        private ClassMetadataFactory $classMetadataFactory,
        private iterable $hydrators
    ) {
    }

    public function getHydrator(string|object $class): HydratorInterface
    {
        if (is_object($class)) {
            $class = $class::class;
        }

        $classMetadata = $this->classMetadataFactory->getMetadata($class);
        $hydratorClass = $classMetadata->getHydratorClass();

        $hydrator = null;

        foreach ($this->hydrators as $key => $value) {
            if ($key == $hydratorClass) {
                $hydrator = $value;
            }
        }

        return $hydrator;
    }
}
