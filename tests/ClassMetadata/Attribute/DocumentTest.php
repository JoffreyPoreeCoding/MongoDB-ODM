<?php

declare(strict_types=1);

/*
 * This file is part of jpc/mongodb-odm.
 * (c) Joffrey Porée <poree.joffrey@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JPC\MongoDB\ODM\Tests\ClassMetadata\Attribute;

use JPC\MongoDB\ODM\ClassMetadata\Attribute\Document;
use JPC\MongoDB\ODM\ClassMetadata\ClassMetadata;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function test_map(): void
    {
        $documentAttribute = new Document('collection_name');

        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $classMetadataMock->expects($this->once())->method('setCollection')->with('collection_name')->willReturnSelf();

        $documentAttribute->map($classMetadataMock);
    }
}
