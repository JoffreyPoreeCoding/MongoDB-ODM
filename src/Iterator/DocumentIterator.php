<?php

namespace JPC\MongoDB\ODM\Iterator;

use Iterator;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\EventManager;
use MongoDB\Driver\Cursor;

class DocumentIterator implements Iterator {

    protected $data;

    protected $objectClass;

    protected $repository;

    protected $hydrator;

    protected $classMetadata;

    protected $documentManager;

    protected $generator;

    protected $currentData;

    protected $position = 0;

    protected $readOnly = false;

    public function __construct($data, $objectClass, Repository $repository)
    {
        $this->data     = $data;
        $this->objectClass     = $objectClass;
        $this->repository      = $repository;
        $this->hydrator        = $repository->getHydrator();
        $this->classMetadata   = $repository->getClassMetadata();
        $this->documentManager = $repository->getDocumentManager();
        $this->generator       = $this->createGenerator();
        $this->currentData     = $this->generator->current();
    }

    public function readOnly(){
        $this->readOnly = true;
    }

    /**
     * Rewinds the Iterator to the first element.
     */
    public function rewind()
    {
        //throw new \Exception('Method rewind() is not implemented.');
    }

    /**
     * Checks if there is a current element after calls to rewind() or next().
     *
     * @return bool
     */
    public function valid()
    {
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
     * @return \PHPUnit_Framework_Test
     */
    public function current()
    {
        $class = $this->objectClass;
        $object = new $class();
        $this->hydrator->hydrate($object, $this->currentData);
        $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_LOAD, $object);
        if(!$this->readOnly){
            $this->repository->cacheObject($object);
            $this->documentManager->addObject($object, DocumentManager::OBJ_MANAGED, $this);
        }
        return $object;
    }

    /**
     * Moves forward to next element.
     */
    public function next()
    {
        $this->position++;
        $this->currentItem = $this->generator->next();
    }

    private function createGenerator(){
        foreach($this->data as $data){
            yield $data;
        }
    }
}