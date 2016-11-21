<?php

use JPC\MongoDB\ODM\DocumentManager;

require_once __DIR__ . "/../models/MultiEmbeddedDocument.php";

class UpdateMultiEmbeddedTest extends PHPUnit_Framework_TestCase {

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
       $this->dm = new DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");

        $this->rep = $this->dm->getRepository("MultiEmbeddedDocument");
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
            "multi_embedded" => [
                [
                    "attr_1" => "value1",
                    "attr_2" => "value2",
                    "embedded_1" => [
                        "attr_1" => "value3",
                        "attr_2" => "value4",
                    ]
                ],
                [
                    "attr_1" => "value5",
                    "attr_2" => "value6"
                ]
            ]
        ]);
        
        $doc = $this->rep->find("simple");
        
        $doc->getMultiEmbedded()[0]->setAttr1("value7")->setAttr2("value8");
        $doc->getMultiEmbedded()[0]->getEmbedded()->setAttr1("value9")->setAttr2("value10");
        
        $embedded = new EmbeddedTwo();
        $embedded->setAttr1("value11")->setAttr2("value12");
        
        $doc->getMultiEmbedded()[1]->setAttr1("value13")->setAttr2("value14");
        $doc->getMultiEmbedded()[1]->setEmbedded($embedded);

        $this->dm->flush();

        $this->assertEquals("value7", $doc->getMultiEmbedded()[0]->getAttr1());
        $this->assertEquals("value8", $doc->getMultiEmbedded()[0]->getAttr2());
    }

//    public function test_updateArray() {
//        $this->insertObject([
//            "_id" => "embedded_array",
//            "embedded_1" => [
//                "attr_1" => ["a", "b", "c"]
//            ]
//        ]);
//
//        $doc = $this->rep->find("embedded_array");
//        $doc->getEmbedded()->setAttr1(["a", "d"]);
//
//        $this->dm->flush();
//
//        $expected = $this->getObject("embedded_array")->embedded_1;
//
//        $this->assertEquals($expected->attr_1[0], $doc->getEmbedded()->getAttr1()[0]);
//        $this->assertEquals($expected->attr_1[1], $doc->getEmbedded()->getAttr1()[1]);
//        $this->assertNull($expected->attr_1[2]);
//    }
//
//    public function test_updateObject() {
//        $this->insertObject([
//            "_id" => "embedded_object",
//            "embedded_1" => [
//                "attr_1" => ["a" => "a", "b" => "b", "c" => "c"]
//            ]
//        ]);
//
//        $doc = $this->rep->find("embedded_object");
//        $doc->getEmbedded()->getAttr1()->b = "d";
//        $doc->getEmbedded()->getAttr1()->c = null;
//
//        $this->dm->flush();
//
//        $expected = $this->getObject("embedded_object")->embedded_1;
//
//        $this->assertEquals($expected->attr_1->a, $doc->getEmbedded()->getAttr1()->a);
//        $this->assertEquals($expected->attr_1->b, $doc->getEmbedded()->getAttr1()->b);
//        $this->assertObjectNotHasAttribute("c", $expected->attr_1);
//    }
//
//    public function test_insertDateTime() {
//        $this->insertObject([
//            "_id" => "embedded_date",
//            "embedded_1" => [
//                "attr_1" => new \MongoDB\BSON\UTCDateTime(time() * 1000),
//            ]
//        ]);
//
//        $doc = $this->rep->find("embedded_date");
//
//        $date = new \DateTime();
//        $date->add(new DateInterval("P1D"));
//
//        $doc->getEmbedded()->setAttr1($date);
//
//        $this->dm->flush();
//
//        $expected = $this->getObject("embedded_date");
//
//        $this->assertEquals($date, $expected->embedded_1->attr_1->toDateTime());
//    }
//
//    public function test_insertSimpleOnNull() {
//        $this->insertObject([
//            "_id" => "simple_null",
//            "embedded_1" => null
//        ]);
//
//        $doc = $this->rep->find("simple");
//
//        $doc->getEmbedded()->setAttr1("value3");
//        $doc->getEmbedded()->setAttr2("value4");
//
//        $this->dm->flush();
//
//        $expected = $this->getObject("simple")->embedded_1;
//
//        $this->assertEquals($expected->attr_1, $doc->getEmbedded()->getAttr1());
//        $this->assertEquals($expected->attr_2, $doc->getEmbedded()->getAttr2());
//    }

    private function insertObject($data) {
        return $this->dm->getMongoDBDatabase()->selectCollection("multi_embedded_doc")->insertOne($data);
    }

    private function getObject($id) {
        return $this->dm->getMongoDBDatabase()->selectCollection("multi_embedded_doc")->findOne(["_id" => $id]);
    }

    /**
     * @afterClass
     */
    public static function removeDatabase() {
        (new DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit"))->getMongoDBDatabase()->drop();
    }

}
