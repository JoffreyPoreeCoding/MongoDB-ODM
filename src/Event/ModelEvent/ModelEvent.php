<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Repository;
use Symfony\Contracts\EventDispatcher\Event;

abstract class ModelEvent extends Event
{
    const NAME = 'model.model_event';

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var Repository
     */
    protected $repository;
    
    /**
     * @var mixed
     */
    protected $document;

    public function __construct(DocumentManager $documentManager, Repository $repository, $document)
    {
        $this->documentManager = $documentManager;
        $this->repository = $repository;
        $this->document = $document;
    }

    /**
     * Get the value of documentManager
     *
     * @return DocumentManager
     */ 
    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * Get the value of repository
     *
     * @return Repository
     */ 
    public function getRepository()
    {
        return $this->repository;
    }
    
    /**
     * Get the value of repository
     *
     * @return mixed
     */ 
    public function getDocument()
    {
        return $this->document;
    }
}