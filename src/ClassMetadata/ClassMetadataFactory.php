<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\ClassMetadata;

use JPC\MongoDB\ODM\ClassMetadata\Parser\ClassMetadataParserInterface;
use JPC\MongoDB\ODM\Configuration\Configuration;
use ReflectionClass;

class ClassMetadataFactory
{
    public function __construct(
        private Configuration $configuration
    ) {
    }

    public function getMetadata(string $className): ClassMetadata
    {
        $cacheKey             = preg_replace("~[\{\}\(\)/\\\\@:]~", '_', $className);
        $classMetadataFactory = $this;

        return $this->configuration->getCache()->get(
            $cacheKey,
            static fn () => $classMetadataFactory->createMetadata($className)
        );
    }

    public function createMetadata(string $className): ClassMetadata
    {
        $class = new ReflectionClass($className);

        $parsers = $this->configuration->getMetadataParsers();

        $classMetadata = new ClassMetadata($this->configuration);
        $classMetadata->setClassName($class->getName());
        $classMetadata->setNamespace($class->getNamespaceName());

        /** @var ClassMetadataParserInterface $parser */
        foreach ($parsers as $parser) {
            $parser->parse($class, $classMetadata);
        }

        foreach ($class->getProperties() as $property) {
            $propertyMetadata = new PropertyMetadata();
            $propertyMetadata->setName($property->getName());

            /** @var ClassMetadataParserInterface $parser */
            foreach ($parsers as $parser) {
                $parser->parse($property, $propertyMetadata);
            }

            $classMetadata->addProperty($propertyMetadata);
        }

        return $classMetadata;
    }
}
