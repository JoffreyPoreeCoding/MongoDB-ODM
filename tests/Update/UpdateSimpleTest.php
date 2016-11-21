<?php

use JPC\MongoDB\ODM\DocumentManager;

require_once __DIR__."/../models/SimpleDocument.php";

class UpdateSimpleTest extends PHPUnit_Framework_TestCase {
    
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
        
        $this->rep = $this->dm->getRepository("SimpleDocument");
    }
    
    /**
     * @before
     */
    public function clear(){
        $this->dm->clear();
    }
    
    public function test_updateSimple(){
        $this->insertObject([
           "_id" => "simple",
            "attr_1" => "value1",
            "attr_2" => "value2"
        ]);
        
        $doc = $this->rep->find("simple");
        $doc->setAttr1("value3");
        $doc->setAttr2("value4");
        
        $this->dm->flush();
        
        $expected = $this->getObject("simple");
        
        $this->assertEquals($expected->attr_1, $doc->getAttr1());
        $this->assertEquals($expected->attr_2, $doc->getAttr2());
    }
    
    public function test_updateArray(){
        $this->insertObject([
           "_id" => "simple_array",
            "attr_1" => ["a", "b", "c"],
            "attr_2" => "value2"
        ]);
        
        $doc = $this->rep->find("simple_array");
        $doc->setAttr1(["a", "d"]);
        
        $this->dm->flush();
        
        $expected = $this->getObject("simple_array");
        
        $this->assertEquals($expected->attr_1[0], $doc->getAttr1()[0]);
        $this->assertEquals($expected->attr_1[1], $doc->getAttr1()[1]);
        $this->assertNull($expected->attr_1[2]);
        
    }
    
    public function test_updateObject(){
        $this->insertObject([
           "_id" => "simple_object",
            "attr_1" => ["a" => "a", "b" => "b", "c" => "c"],
        ]);
        
        $doc = $this->rep->find("simple_object");
        
        $obj = new \stdClass();
        $obj->a = "a";
        $obj->b = "d";
        
        $doc->setAttr1($obj);
        
        $this->dm->flush();
        
        $expected = $this->getObject("simple_object");
        
        $this->assertEquals($expected->attr_1->a, $doc->getAttr1()->a);
        $this->assertEquals($expected->attr_1->b, $doc->getAttr1()->b);
        $this->assertObjectNotHasAttribute("c", $expected->attr_1);
    }
    
    public function test_insertDateTime(){
        $this->insertObject([
           "_id" => "simple_date",
            "attr_1" => new \MongoDB\BSON\UTCDateTime(time() * 1000),
        ]);
        
        $doc = $this->rep->find("simple_date");
        
        $date = new \DateTime();
        $date->add(new DateInterval("P1D"));
        
        $doc->setAttr1($date);
        
        $this->dm->flush();
        
        $expected = $this->getObject("simple_date");
        
        $this->assertEquals($date, $expected->attr_1->toDateTime());
        
    }
    
    private function insertObject($data){
        return $this->dm->getMongoDBDatabase()->selectCollection("simple_doc")->insertOne($data);
    }
    
    private function getObject($id){
        return $this->dm->getMongoDBDatabase()->selectCollection("simple_doc")->findOne(["_id" => $id]);
    }
    
    /**
     * @afterClass
     */
    public static function removeDatabase(){
       (new DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit"))->getMongoDBDatabase()->drop();
    }
}
