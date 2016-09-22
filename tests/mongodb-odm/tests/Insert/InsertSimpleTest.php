<?php

use JPC\MongoDB\ODM\DocumentManager;

require_once __DIR__."/../../models/SimpleDocument.php";

class InsertSimpleTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Document Manager
     * @var DocumentManager
     */
    private $dm;
    
    public function __construct() {
        $this->dm = DocumentManager::instance("mongodb://localhost", "jpc_mongodb_phpunit");
        $this->dm->addModelPath("globals", __DIR__."/../../models/");
    }
    
    public function test_insertSimple(){
        $simple = new SimpleDocument();
        $simple->setAttr1("test1");
        $simple->setAttr2("test2");
        
        $this->dm->persist($simple);
        $this->dm->flush();
        
        $inserted = $this->getObject($simple->getId());
        
        $this->assertEquals($simple->getId(), $inserted["_id"]);
        $this->assertEquals($simple->getAttr1(), $inserted["attr_1"]);
        $this->assertEquals($simple->getAttr2(), $inserted["attr_2"]);
    }
    
    public function test_insertArray(){
        $simple = new SimpleDocument();
        $simple->setAttr1(["a", "b", "c"]);
        
        $this->dm->persist($simple);
        $this->dm->flush();
        
        $inserted = $this->getObject($simple->getId());
        
        $this->assertEquals($simple->getId(), $inserted["_id"]);
        $this->assertEquals($simple->getAttr1(), (array)$inserted["attr_1"]);
        $this->assertArrayNotHasKey("attr_2", $inserted);
    }
    
    public function test_insertObject(){
        $obj = new \stdClass();
        $obj->a = 1;
        $obj->b = 2;
        $obj->c = 3;
        
        $simple = new SimpleDocument();
        $simple->setAttr1($obj);
        
        $this->dm->persist($simple);
        $this->dm->flush();
        
        $inserted = $this->getObject($simple->getId());
        
        $this->assertEquals($simple->getId(), $inserted["_id"]);
        $this->assertEquals((array)$simple->getAttr1(), (array)$inserted["attr_1"]);
        $this->assertArrayNotHasKey("attr_2", $inserted);
    }
    
    public function test_insertDateTime(){
        $date = new \DateTime();
        
        $simple = new SimpleDocument();
        $simple->setAttr1($date);
        
        $this->dm->persist($simple);
        $this->dm->flush();
        
        $inserted = $this->getObject($simple->getId());
        
        $this->assertEquals($simple->getId(), $inserted["_id"]);
        $this->assertEquals($simple->getAttr1(), $inserted["attr_1"]->toDateTime());
        $this->assertArrayNotHasKey("attr_2", $inserted);
    }
    
    private function getObject($id){
        return $this->dm->getMongoDBDatabase()->selectCollection("simple_doc")->findOne(["_id" => $id]);
    }
    
    /**
     * @afterClass
     */
    public static function removeDatabase(){
       DocumentManager::instance("mongodb://localhost", "jpc_mongodb_phpunit")->getMongoDBDatabase()->drop();
    }
}
