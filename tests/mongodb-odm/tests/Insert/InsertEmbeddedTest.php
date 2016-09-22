<?php

use JPC\MongoDB\ODM\DocumentManager;

require_once __DIR__."/../../models/EmbeddedDocument.php";

class InsertEmbeddedTest extends PHPUnit_Framework_TestCase {
    
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
        $document = new EmbeddedDocument();
        
        $embedded = new Embedded();
        $embedded->setAttr1("test1");
        $embedded->setAttr2("test2");
        
        $document->setEmbedded($embedded);
        
        $this->dm->persist($document);
        $this->dm->flush();
        
        $inserted = $this->getObject($document->getId());
        
        $insertedEmbedded = $inserted["embedded"];
        
        $this->assertInstanceOf("MongoDB\Model\BSONDocument", $insertedEmbedded);
        $this->assertEquals($embedded->getAttr1(), $insertedEmbedded["attr_1"]);
        $this->assertEquals($embedded->getAttr2(), $insertedEmbedded["attr_2"]);
    }
    
    public function test_insertArray(){
        $document = new EmbeddedDocument();
        
        $embedded = new Embedded();
        $embedded->setAttr1(["a", "b", "c"]);
        
        $document->setEmbedded($embedded);
        
        $this->dm->persist($document);
        $this->dm->flush();
        
        $inserted = $this->getObject($document->getId());
        
        $insertedEmbedded = $inserted["embedded"];
        
        $this->assertInstanceOf("MongoDB\Model\BSONDocument", $insertedEmbedded);
        $this->assertEquals($embedded->getAttr1(), (array)$insertedEmbedded["attr_1"]);
        $this->assertArrayNotHasKey("attr_2", (array)$insertedEmbedded);
    }
    
    public function test_insertObject(){
        $obj = new \stdClass();
        $obj->a = 1;
        $obj->b = 2;
        $obj->c = 3;
        
        $document = new EmbeddedDocument();
        
        $embedded = new Embedded();
        $embedded->setAttr1($obj);
        
        $document->setEmbedded($embedded);
        
        $this->dm->persist($document);
        $this->dm->flush();
        
        $inserted = $this->getObject($document->getId());
        
        $insertedEmbedded = $inserted["embedded"];
        
        $this->assertInstanceOf("MongoDB\Model\BSONDocument", $insertedEmbedded);
        $this->assertEquals((array)$embedded->getAttr1(), (array)$insertedEmbedded["attr_1"]);
        $this->assertArrayNotHasKey("attr_2", (array)$insertedEmbedded);
    }
    
    public function test_insertDateTime(){
        $date = new \DateTime();
        
        $document = new EmbeddedDocument();
        
        $embedded = new Embedded();
        $embedded->setAttr1($date);
        
        $document->setEmbedded($embedded);
        
        $this->dm->persist($document);
        $this->dm->flush();
        
        $inserted = $this->getObject($document->getId());
        
        $insertedEmbedded = $inserted["embedded"];
        
        $this->assertInstanceOf("MongoDB\Model\BSONDocument", $insertedEmbedded);
        $this->assertEquals($embedded->getAttr1(), $insertedEmbedded["attr_1"]->toDateTime());
        $this->assertArrayNotHasKey("attr_2", (array)$insertedEmbedded);
    }
    
    private function getObject($id){
        return $this->dm->getMongoDBDatabase()->selectCollection("embedded_doc")->findOne(["_id" => $id]);
    }
    
    /**
     * @afterClass
     */
    public static function removeDatabase(){
       DocumentManager::instance("mongodb://localhost", "jpc_mongodb_phpunit")->getMongoDBDatabase()->drop();
    }
}
