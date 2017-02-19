<?php

namespace JPC\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Tools\Logger\LoggerInterface;
use JPC\MongoDB\ODM\Tools\Logger\MemoryLogger;
use MongoDB\Client;
use MongoDB\Database;

class DocumentManagerFactory {

	/**
	 * Create new DocumentManager from mongouri and DB name
	 * 
	 * @param  string  				$mongouri mongodb uri (mongodb://user:pass@example.org/auth_db)
	 * @param  string  				$dbName   name of the DB where to work
	 * @param  LoggerInterface  	$logger   A logger class
	 * @param  boolean 				$debug    Enable debug mode or not
	 * 
	 * @return DocumentManager      A DocumentManager connected to mongouri specified
	 */
	public function createDocumentManager($mongouri, $dbName, $logger = null, $debug = false){
		$client = new Client($mongouri);
		$database = new Database($client->getManager(), $dbName);
		$repositoryFactory = new RepositoryFactory();

		$logger = isset($logger) ?: new MemoryLogger();

		return new DocumentManager(
			$client, 
			$database, 
			$repositoryFactory,
			$logger, 
			$debug
		);
	}
}