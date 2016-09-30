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
        $om = JPC\MongoDB\ODM\ObjectManager::getInstance();
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

    public function testClearNullValues() {
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");

        $method = $this->reflection->getMethod('clearNullValues');
        $method->setAccessible(true);

        $query = [
            "a" => false,
            "b" => null,
            "c" => [
                "1a" => 0,
                "1b" => null
            ],
            "d" => [
                ['2a' => 1, '2b' => null]
            ]
        ];

        $expected = [
            "a" => false,
            "c" => [
                "1a" => 0,
            ],
            "d" => [
                ['2a' => 1]
            ]
        ];

        $ref =&$query;

        $this->assertEquals($expected, $method->invoke($dm, $ref));
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
    
    public function testAggregArray(){
        $dm = new JPC\MongoDB\ODM\DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");

        $method = $this->reflection->getMethod('aggregArray');
        $method->setAccessible(true);

        $query = [
                "a" => ['b' => ["c" => 1, "d" => 2]]
        ];

        $expected = [
            "a.b.c" => 1,
            "a.b.d" => 2
        ];

        $this->assertEquals($expected, $method->invoke($dm, $query["a"], "a"));
    }
    
    

}
