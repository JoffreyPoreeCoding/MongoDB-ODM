<?php

namespace JPC\Test\MongoDB\ODM\Id;

use PHPUnit\Framework\TestCase;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Id\DefaultGenerator;

class DefaultGeneratorTest extends TestCase
{
    public function testGenerateId()
    {
        $generator = new DefaultGenerator();
        $id = $generator->generate($this->createMock(DocumentManager::class), new \stdClass());

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $id);
    }
}
