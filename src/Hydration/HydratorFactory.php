<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration;

use JPC\MongoDB\ODM\Configuration\Configuration;

class HydratorFactory
{
    public function __construct(
        private Configuration $configuration
    ) {
    }

    public function getHydrator(object|string $object): HydratorInterface
    {
        if (is_object($object)) {
            $object = $object::class;
        }

        $classMetadataFactory = $this->configuration->getClassMetadataFactory();
        $classMetadata        = $classMetadataFactory->getMetadata($object);

        $hydratorClass = $classMetadata->getHydratorClass();

        return new $hydratorClass($classMetadata, $this->configuration);
    }
}
