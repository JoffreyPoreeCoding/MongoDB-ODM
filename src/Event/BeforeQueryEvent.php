<?php

namespace JPC\MongoDB\ODM\Event;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeQueryEvent extends Event
{
    const NAME = 'query.before';

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var Repository
     */
    protected $repository;

    public function __construct(DocumentManager $documentManager, Repository $repository, Query $query = null)
    {
        $this->query = $query;
        $this->documentManager = $documentManager;
        $this->repository = $repository;
    }

    /**
     * Get the value of query
     *
     * @return  Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the value of documentManager
     *
     * @return  DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * Get the value of repository
     *
     * @return  Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
