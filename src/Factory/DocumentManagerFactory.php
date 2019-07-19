<?php

namespace JPC\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\DocumentManager;
use MongoDB\Client;
use MongoDB\Database;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Factory to create document manager easily
 */
class DocumentManagerFactory
{

    /**
     * Repository factory class
     * @var string
     */
    protected $repositoryFactoryClass;

    /**
     * Already opened connexions
     * @var Client
     */
    protected $connexions = [];

    /**
     * Already created document managers
     * @var DocumentManager
     */
    protected $managers = [];

    /**
     * Class metadata factory
     * @var ClassMetadataFactory
     */
    protected $classMetadataFactory;

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
     * @param   boolean                 $debug          Enable debug mode or not
     * @param   mixed                   $managerId      Manager unique id
     * @param   boolean                 $newConnection  Force to open new connection
     *
     * @return DocumentManager      A DocumentManager connected to mongouri specified
     */
    public function createDocumentManager($mongouri, $dbName, $debug = false, $managerId = "", $newConnection = false, $options = [])
    {

        if (!isset($this->connexions[$mongouri])) {
            $client = new Client($mongouri);
        }

        if (!isset($this->managers[$managerId])) {
            $database = new Database($client->getManager(), $dbName);

            $class = $this->repositoryFactoryClass;
            $eventDispatcher = new EventDispatcher();
            $repositoryFactory = new $class($eventDispatcher, null, $this->classMetadataFactory);

            $this->managers[$managerId] = new DocumentManager(
                $client,
                $database,
                $repositoryFactory,
                $debug,
                $options,
                [],
                $eventDispatcher
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
