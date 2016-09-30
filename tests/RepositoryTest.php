<?php

require_once __DIR__ . "/models/EmbeddedDocument.php";

class RepositoryTest extends PHPUnit_Framework_TestCase {

    /**
     * Reflection class
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * Repository
     * @var \JPC\MongoDB\ODM\Repository
     */
    private $rep;

    public function __construct() {
        apcu_clear_cache();
        JPC\MongoDB\ODM\DocumentManager::getInstance("mongodb://localhost", "jpc_mongodb_phpunit");
        $this->reflection = new ReflectionClass("JPC\MongoDB\ODM\Repository");
        $this->rep = new \JPC\MongoDB\ODM\Repository(\JPC\MongoDB\ODM\Tools\ClassMetadataFactory::getInstance()->getMetadataForClass("EmbeddedDocument"), "repo_test");
    }

    public function testCastMongoQuery() {
        $method = $this->reflection->getMethod('castMongoQuery');
        $method->setAccessible(true);

        $query = [
            "embedded" => [
                "attr1" => ['$lt' => 20, '$gt' => -100],
            ]
        ];
        
        $expected = [
            "embedded_1.attr_1" => ['$lt' => 20, '$gt' => -100]
        ];
        
        dump($method->invoke($this->rep, $query));
        
        $this->assertEquals($expected, $method->invoke($this->rep, $query));
    }

}
