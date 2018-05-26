<?php

namespace JPC\MongoDB\ODM\Iterator;

use Iterator;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\EventManager;

class DocumentIterator implements Iterator, \Countable
{

    protected $data;

    protected $objectClass;

    protected $repository;

    protected $query;

    protected $hydrator;

    protected $classMetadata;

    protected $documentManager;

    protected $generator;

    protected $currentData;

    protected $position = 0;

    protected $readOnly = false;

    protected $count;

    protected $rewindable = false;

    public function __construct($data, $objectClass, Repository $repository, $query = [])
    {
        $this->data = $data;
        $this->objectClass = $objectClass;
        $this->repository = $repository;
        $this->query = $query;
        $this->hydrator = $repository->getHydrator();
        $this->classMetadata = $repository->getClassMetadata();
        $this->documentManager = $repository->getDocumentManager();
        $this->objects = [];

        $this->generator = $this->createGenerator();
        $this->currentData = $this->generator->current();
    }

    public function readOnly()
    {
        $this->readOnly = true;
    }

    /**
     * Rewinds the Iterator to the first element.
     */
    public function rewind()
    {
        if (!$this->rewindable) {
            throw new \Exception('Unable to traverse not rewindable iterator multiple time');
        }
        $this->position = 0;
    }

    /**
     * Checks if there is a current element after calls to rewind() or next().
     *
     * @return bool
     */
    public function valid()
    {
        if (isset($this->objects[$this->position])) {
            return true;
        }

        return $this->generator->valid();
    }

    /**
     * Returns the key of the current element.
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Returns the current element.
     *
     * @return mixed
     */
    public function current()
    {
        if (!isset($this->objects[$this->position])) {
            $class = $this->objectClass;
            $object = new $class();
            $this->hydrator->hydrate($object, $this->currentData);
            $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_LOAD, $object);
            if (!$this->readOnly) {
                $this->repository->cacheObject($object);
                $this->documentManager->addObject($object, DocumentManager::OBJ_MANAGED, $this->repository);
            }
            if ($this->rewindable) {
                $this->objects[] = $object;
            } else {
                return $object;
            }
        }
        return $this->objects[$this->position];
    }

    /**
     * Moves forward to next element.
     */
    public function next()
    {
        $this->position++;
        $this->generator->next();
        $this->currentData = $this->generator->current();
    }

    protected function createGenerator()
    {
        foreach ($this->data as $data) {
            yield $data;
        }
    }

    public function rewindable()
    {
        $this->rewindable = true;
    }

    public function count()
    {
        if (!isset($this->count)) {
            $this->count = $this->repository->count($this->query);
        }
        return $this->count;
    }
}
