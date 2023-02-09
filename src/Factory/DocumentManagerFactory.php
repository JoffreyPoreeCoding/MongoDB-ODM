<?php

namespace JPC\MongoDB\ODM\Factory;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Subscriber\ModelEventSubscriber;
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
        $newClient = false;
        if (!isset($this->connexions[$mongouri]) || $newConnection) {
            $this->connexions[$mongouri] = new Client($mongouri);
            $newClient = true;
        }

        if (!isset($this->managers[$managerId]) || $newClient) {
            $database = new Database($this->connexions[$mongouri]->getManager(), $dbName, $options);

            $class = $this->repositoryFactoryClass;

            $eventDispatcher = new EventDispatcher();
            if (isset($options['event_subscribers'])) {
                foreach ($options['event_subscribers'] as $subscriber) {
                    $eventDispatcher->addSubscriber($subscriber);
                }
            }
            $modelEventSubscriber = new ModelEventSubscriber();
            $eventDispatcher->addSubscriber($modelEventSubscriber);

            $repositoryFactory = new $class($eventDispatcher, null, $this->classMetadataFactory);

            $this->managers[$managerId] = new DocumentManager(
                $this->connexions[$mongouri],
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
