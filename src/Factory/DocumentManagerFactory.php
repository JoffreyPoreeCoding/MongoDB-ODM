<?php

namespace JPC\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Tools\Logger\LoggerInterface;
use JPC\MongoDB\ODM\Tools\Logger\MemoryLogger;
use MongoDB\Client;
use MongoDB\Database;

/**
 * Factory to create document manager easily
 */
class DocumentManagerFactory
{

    /**
     * Repository factory class
     * @var string
     */
    private $repositoryFactoryClass;

    /**
     * Already opened connexions
     * @var Client
     */
    private $connexions = [];

    /**
     * Already created document managers
     * @var DocumentManager
     */
    private $managers = [];

    /**
     * Class metadata factory
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;

    /**
     * Create a document manager factory
     *
     * @param   string  $repositoryFactoryClass class of repository factory
     */
    public function __construct($repositoryFactoryClass = null)
    {
        $this->repositoryFactoryClass = isset($repositoryFactoryClass) ? $repositoryFactoryClass : "JPC\MongoDB\ODM\Factory\RepositoryFactory";

        $this->classMetadataFactory = new ClassMetadataFactory();
    }

    /**
     * Create new DocumentManager from mongouri and DB name
     *
     * @param   string                  $mongouri       mongodb uri (mongodb://user:pass@example.org/auth_db)
     * @param   string                  $dbName         name of the DB where to work
     * @param   LoggerInterface         $logger         A logger class
     * @param   boolean                 $debug          Enable debug mode or not
     * @param   mixed                   $managerId      Manager unique id
     * @param   boolean                 $newConnection  Force to open new connection
     *
     * @return DocumentManager      A DocumentManager connected to mongouri specified
     */
    public function createDocumentManager($mongouri, $dbName, $logger = null, $debug = false, $managerId = "", $newConnection = false, $options = [])
    {

        if (!isset($this->connexions[$mongouri])) {
            $client = new Client($mongouri);
        }

        if (!isset($this->managers[$managerId])) {
            $database = new Database($client->getManager(), $dbName);

            $class = $this->repositoryFactoryClass;

            $repositoryFactory = new $class(null, $this->classMetadataFactory);

            $logger = isset($logger) ?: new MemoryLogger();

            $this->managers[$managerId] = new DocumentManager(
                $client,
                $database,
                $repositoryFactory,
                $logger,
                $debug,
                $options,
                []
            );
        }

        return $this->managers[$managerId];
    }

    /**
     * Clear all document manager caches, unpersist all objects
     *
     * @return void
     */
    public function clearAll()
    {
        foreach ($this->managers as $manager) {
            $manager->clear();
        }
    }
}
