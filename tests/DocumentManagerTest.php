<?php

require_once __DIR__ . "/models/SimpleDocument.php";

class DocumentManagerTest extends PHPUnit_Framework_TestCase {

    /**
     * Reflection class
     * @var \ReflectionClass
     */
    private $reflection;

    function __construct() {
        $this->reflection = new ReflectionClass("JPC\MongoDB\ODM\DocumentManager");
    }

    public function testAddModelPath() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");
        $dm->addModelPath("global", "test");

        $prop = $this->reflection->getProperty("modelPaths");
        $prop->setAccessible(true);
        $this->assertEquals("test", $prop->getValue($dm)["global"]);
    }

    public function testGetRepository() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");
        $dm->addModelPath("tests", __DIR__ . "/models");

        $rep = $dm->getRepository("SimpleDocument");

        $this->assertInstanceOf("JPC\MongoDB\ODM\Repository", $rep);
    }

    public function testPersistDeleteUnpersist() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");
        $om = $this->getPropertyValue("objectManager", $dm);
        $obj = new SimpleDocument();

        $dm->persist($obj);
        $this->assertNotNull($om->getObjectState($obj));

        $om->setObjectState($obj, JPC\MongoDB\ODM\ObjectManager::OBJ_MANAGED);

        $dm->delete($obj);
        $this->assertEquals(JPC\MongoDB\ODM\ObjectManager::OBJ_REMOVED, $om->getObjectState($obj));

        $dm->unpersist($obj);
        $this->assertNull($om->getObjectState($obj));
    }

    public function testRefresh() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");
        $dm->addModelPath("tests", __DIR__ . "/models");

        $doc = new SimpleDocument();
        $doc->setAttr1("test1");
        $doc->setAttr2("test2");

        $dm->persist($doc);
        $dm->flush();

        $query = [
            "attr_1" => "test3",
            "attr_2" => "test4"
        ];

        $dm->getMongoDBDatabase()->selectCollection("simple_doc")->updateOne(["_id" => $doc->getId()], ['$set' => $query]);

        $this->assertNotEquals($query["attr_1"], $doc->getAttr1());
        $this->assertNotEquals($query["attr_2"], $doc->getAttr2());

        $dm->refresh($doc);

        $this->assertEquals($query["attr_1"], $doc->getAttr1());
        $this->assertEquals($query["attr_2"], $doc->getAttr2());
    }

    public function testCreateUpdateQueryStatement() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");

        $method = $this->reflection->getMethod('createUpdateQueryStatement');
        $method->setAccessible(true);

        $query = [
            "a" => 1,
            "b" => ['$push' => ["c", "d", "e"]]
        ];

        $expected = [
            '$set' => [
                "a" => 1
            ],
            '$push' => [
                'b' => [
                    '$each' => [
                        "c", "d", "e"
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $method->invoke($dm, $query));
    }

    public function testCheckPush() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");

        $method = $this->reflection->getMethod('checkPush');
        $method->setAccessible(true);

        $query = [
            [
                "a" => ['$push' => ["a", "b", "c"]]
            ]
        ];

        $expected = [
            "b.0.a" => ["a", "b", "c"]
        ];

        $this->assertEquals($expected, $method->invoke($dm, $query, "b"));
    }
    
    private function getPropertyValue($name, $obj){
        $prop = $this->reflection->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }
}
