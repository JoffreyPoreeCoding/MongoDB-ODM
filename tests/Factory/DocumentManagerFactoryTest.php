<?php

namespace JPC\Test\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Factory\DocumentManagerFactory;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class DocumentManagerFactoryTest extends TestCase
{

    public function testCreateDocumentManager()
    {
        $documentManagerFactory = new DocumentManagerFactory();
        $documentManager = $documentManagerFactory->createDocumentManager("mongodb://localhost", "test");

        $this->assertInstanceOf(DocumentManager::class, $documentManager);
    }
}
