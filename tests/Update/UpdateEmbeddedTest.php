<?php

use JPC\MongoDB\ODM\DocumentManager;

require_once __DIR__ . "/../models/EmbeddedDocument.php";

class UpdateEmbeddedTest extends PHPUnit_Framework_TestCase {

    /**
     * Document Manager
     * @var DocumentManager
     */
    private $dm;

    /**
     * Repository
     * @var Repository
     */
    private $rep;

    public function __construct() {
        $this->dm = DocumentManager::getInstance("mongodb://localhost", "jpc_mongodb_phpunit");
        $this->dm->addModelPath("globals", __DIR__ . "/../models/");

        $this->rep = $this->dm->getRepository("EmbeddedDocument");
    }

    /**
     * @before
     */
    public function clear() {
        $this->dm->clear();
    }

    public function test_updateSimple() {
        $this->insertObject([
            "_id" => "simple",
            "embedded_1" => [
                "attr_1" => "value1",
                "attr_2" => "value2"]
        ]);

        $doc = $this->rep->find("simple");
        $doc->getEmbedded()->setAttr1("value3");
        $doc->getEmbedded()->setAttr2("value4");

        $this->dm->flush();

        $expected = $this->getObject("simple")->embedded_1;

        $this->assertEquals($expected->attr_1, $doc->getEmbedded()->getAttr1());
        $this->assertEquals($expected->attr_2, $doc->getEmbedded()->getAttr2());
    }

    public function test_updateArray() {
        $this->insertObject([
            "_id" => "embedded_array",
            "embedded_1" => [
                "attr_1" => ["a", "b", "c"]
            ]
        ]);

        $doc = $this->rep->find("embedded_array");
        $doc->getEmbedded()->setAttr1(["a", "d"]);

        $this->dm->flush();

        $expected = $this->getObject("embedded_array")->embedded_1;

        $this->assertEquals($expected->attr_1[0], $doc->getEmbedded()->getAttr1()[0]);
        $this->assertEquals($expected->attr_1[1], $doc->getEmbedded()->getAttr1()[1]);
        $this->assertNull($expected->attr_1[2]);
    }

    public function test_updateObject() {
        $this->insertObject([
            "_id" => "embedded_object",
            "embedded_1" => [
                "attr_1" => ["a" => "a", "b" => "b", "c" => "c"]
            ]
        ]);

        $doc = $this->rep->find("embedded_object");
        $doc->getEmbedded()->getAttr1()->b = "d";
        $doc->getEmbedded()->getAttr1()->c = null;

        $this->dm->flush();

        $expected = $this->getObject("embedded_object")->embedded_1;

        $this->assertEquals($expected->attr_1->a, $doc->getEmbedded()->getAttr1()->a);
        $this->assertEquals($expected->attr_1->b, $doc->getEmbedded()->getAttr1()->b);
        $this->assertObjectNotHasAttribute("c", $expected->attr_1);
    }

    public function test_insertDateTime() {
        $this->insertObject([
            "_id" => "embedded_date",
            "embedded_1" => [
                "attr_1" => new \MongoDB\BSON\UTCDateTime(time() * 1000),
            ]
        ]);

        $doc = $this->rep->find("embedded_date");

        $date = new \DateTime();
        $date->add(new DateInterval("P1D"));

        $doc->getEmbedded()->setAttr1($date);

        $this->dm->flush();

        $expected = $this->getObject("embedded_date");

        $this->assertEquals($date, $expected->embedded_1->attr_1->toDateTime());
    }

    private function insertObject($data) {
        return $this->dm->getMongoDBDatabase()->selectCollection("embedded_doc")->insertOne($data);
    }

    private function getObject($id) {
        return $this->dm->getMongoDBDatabase()->selectCollection("embedded_doc")->findOne(["_id" => $id]);
    }

    /**
     * @afterClass
     */
    public static function removeDatabase() {
        DocumentManager::getInstance("mongodb://localhost", "jpc_mongodb_phpunit")->getMongoDBDatabase()->drop();
    }

}
