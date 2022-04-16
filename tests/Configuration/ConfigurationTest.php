<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\Configuration;

use JPC\MongoDB\ODM\Configuration\Configuration;
use JPC\MongoDB\ODM\Hydration\AccessorHydrator;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_getDefaultValues(): void
    {
        $configuration = new Configuration();

        $this->assertEquals(AccessorHydrator::class, $configuration->getDefaultHydratorClass());
    }

    public function test_setAndGet(): void
    {
        $configuration = new Configuration();

        $defaultHydratorClass = 'HydratorClass';
        $configuration->setDefaultHydratorClass($defaultHydratorClass);
        $this->assertSame($defaultHydratorClass, $configuration->getDefaultHydratorClass());
    }
}
