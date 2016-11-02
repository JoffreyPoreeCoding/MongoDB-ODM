<?php

require_once __DIR__ . "/models/EmbeddedDocument.php";

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Tools\ClassMetadataFactory;
use JPC\MongoDB\ODM\Repository;

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
        $dm = new DocumentManager("mongodb://localhost", "jpc_mongodb_phpunit");
        $this->reflection = new ReflectionClass("JPC\MongoDB\ODM\Repository");
        $this->rep = $dm->getRepository("EmbeddedDocument", "repo_test");
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

        $this->assertEquals($expected, $method->invoke($this->rep, $query));
    }

}
