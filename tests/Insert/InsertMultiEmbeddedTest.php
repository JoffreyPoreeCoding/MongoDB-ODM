<?php

use JPC\MongoDB\ODM\DocumentManager;

require_once __DIR__ . "/../models/MultiEmbeddedDocument.php";

class InsertMultiEmbeddedTest extends PHPUnit_Framework_TestCase {

    /**
     * Document Manager
     * @var DocumentManager
     */
    private $dm;

    public function __construct() {
        $this->dm = DocumentManager::getInstance("mongodb://localhost", "jpc_mongodb_phpunit");
        $this->dm->addModelPath("globals", __DIR__ . "/../models/");
    }

    public function test_insertSimple() {
        $document = new MultiEmbeddedDocument();

        $embeddeds = [];

        for ($i = 0; $i < 3; $i++) {
            $embedded = new MultiEmbedded();
            $embedded->setAttr1("test" . $i);
            $embedded->setAttr2("test" . $i + 1);
            $document->addMultiEmbedded($embedded);
            $embeddeds[] = $embedded;
        }

        $this->dm->persist($document);
        $this->dm->flush();

        $inserted = $this->getObject($document->getId());

        $insertedEmbeddeds = $inserted["multi_embedded"];

        $this->assertInstanceOf("MongoDB\Model\BSONArray", $insertedEmbeddeds);
        foreach ($embeddeds as $index => $emb) {
            $this->assertEquals($emb->getAttr1(), $insertedEmbeddeds[$index]["attr_1"]);
            $this->assertEquals($emb->getAttr2(), $insertedEmbeddeds[$index]["attr_2"]);
        }
    }

    public function test_insertArray() {
        $document = new MultiEmbeddedDocument();

        $embeddeds = [];

        for ($i = 0; $i < 3; $i++) {
            $embedded = new MultiEmbedded();
            $embedded->setAttr1([$i, $i + 1, $i * 2, $i * 25]);
            $document->addMultiEmbedded($embedded);
            $embeddeds[] = $embedded;
        }

        $this->dm->persist($document);
        $this->dm->flush();

        $inserted = $this->getObject($document->getId());

        $insertedEmbeddeds = $inserted["multi_embedded"];

        $this->assertInstanceOf("MongoDB\Model\BSONArray", $insertedEmbeddeds);
        foreach ($embeddeds as $index => $emb) {
            $this->assertEquals($emb->getAttr1(), (array) $insertedEmbeddeds[$index]["attr_1"]);
            $this->assertArrayNotHasKey("attr_2", $insertedEmbeddeds[$index]);
        }
    }

    public function test_insertObject() {
        $document = new MultiEmbeddedDocument();

        $embeddeds = [];

        for ($i = 0; $i < 3; $i++) {
            $obj = new \stdClass();
            $obj->a = $i;
            $obj->b = $i + 1;
            $obj->c = $i + 2;

            $embedded = new MultiEmbedded();
            $embedded->setAttr1($obj);
            $document->addMultiEmbedded($embedded);
            $embeddeds[] = $embedded;
        }

        $this->dm->persist($document);
        $this->dm->flush();

        $inserted = $this->getObject($document->getId());

        $insertedEmbeddeds = $inserted["multi_embedded"];

        $this->assertInstanceOf("MongoDB\Model\BSONArray", $insertedEmbeddeds);
        foreach ($embeddeds as $index => $emb) {
            $this->assertEquals((array)$emb->getAttr1(), (array) $insertedEmbeddeds[$index]["attr_1"]);
            $this->assertArrayNotHasKey("attr_2", $insertedEmbeddeds[$index]);
        }
    }

    public function test_insertDateTime() {
        $document = new MultiEmbeddedDocument();

        $embeddeds = [];

        for ($i = 0; $i < 3; $i++) {
            $date = new \DateTime();

            $embedded = new MultiEmbedded();
            $embedded->setAttr1($date);
            $document->addMultiEmbedded($embedded);
            $embeddeds[] = $embedded;
        }

        $this->dm->persist($document);
        $this->dm->flush();

        $inserted = $this->getObject($document->getId());

        $insertedEmbeddeds = $inserted["multi_embedded"];

        $this->assertInstanceOf("MongoDB\Model\BSONArray", $insertedEmbeddeds);
        foreach ($embeddeds as $index => $emb) {
            $this->assertEquals($emb->getAttr1(), $insertedEmbeddeds[$index]["attr_1"]->toDateTime());
            $this->assertArrayNotHasKey("attr_2", $insertedEmbeddeds[$index]);
        }
    }

    private function getObject($id) {
        return $this->dm->getMongoDBDatabase()->selectCollection("multi_embedded_doc")->findOne(["_id" => $id]);
    }

    /**
     * @afterClass
     */
    public static function removeDatabase() {
        DocumentManager::getInstance("mongodb://localhost", "jpc_mongodb_phpunit")->getMongoDBDatabase()->drop();
    }

}
