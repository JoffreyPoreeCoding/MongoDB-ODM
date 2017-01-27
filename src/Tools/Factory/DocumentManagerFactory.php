<?php

namespace JPC\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadataFactory;
use JPC\MongoDB\ODM\Tools\Logger\MemoryLogger;
use MongoDB\Client;
use MongoDB\Database;

class DocumentManagerFactory {

	public function createDocumentManager($mongouri, $dbName, $logger, $debug = false){
		$client = new Client($mongouri);
		$database = new Database($client->getManager(), $dbName);
		$classMetadataFactory = new ClassMetadataFactory();
		$objectManager = new ObjectManager();

		$logger = isset($logger) ?: new MemoryLogger();

		return new DocumentManager(
			$client, 
			$database, 
			$classMetadataFactory, 
			$objectManager, 
			$logger, 
			$debug
		);
	}
}