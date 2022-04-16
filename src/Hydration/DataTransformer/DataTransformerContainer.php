<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Hydration\DataTransformer;

use JPC\MongoDB\ODM\ClassMetadata\PropertyMetadata;

class DataTransformerContainer
{
    /**
     * @param DataTransformerInterface[] $dataTransformers
     */
    public function __construct(
        private iterable $dataTransformers
    ) {
    }

    public function transform(PropertyMetadata $field, mixed $value): mixed
    {
        foreach ($this->dataTransformers as $dataTransformer) {
            if ($dataTransformer->supports($field)) {
                $value = $dataTransformer->transform($field, $value);

                break;
            }
        }

        return $value;
    }

    public function reverseTransform(PropertyMetadata $field, mixed $value): mixed
    {
        foreach ($this->dataTransformers as $dataTransformer) {
            if ($dataTransformer->supports($field)) {
                $value = $dataTransformer->reverseTransform($field, $value);

                break;
            }
        }

        return $value;
    }
}
