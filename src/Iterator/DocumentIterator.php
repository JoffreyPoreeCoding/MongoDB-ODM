<?php

namespace JPC\MongoDB\ODM\Iterator;

use Iterator;
use Traversable;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\ModelEvent\PostLoadEvent;

/**
 * Iterator for MongoDB cursor
 */
class DocumentIterator implements Iterator, \Countable
{

    /**
     * Data
     *
     * @var array|Traversable
     */
    protected $data;

    /**
     * Model class
     *
     * @var string
     */
    protected $objectClass;

    /**
     * Repository
     *
     * @var Repository
     */
    protected $repository;

    /**
     * The filter used to get data
     *
     * @var array
     */
    protected $filter;


    /**
     * Generator used to get data
     *
     * @var \Generator
     */
    protected $generator;

    /**
     * Current data
     *
     * @var array
     */
    protected $currentData;

    /**
     * Position in iterator
     *
     * @var integer
     */
    protected $position = 0;

    /**
     * Cache data or no
     *
     * @var boolean
     */
    protected $readOnly = false;

    /**
     * Count values
     *
     * @var integer
     */
    protected $count;

    /**
     * Cursor is rewindable or not
     *
     * @var boolean
     */
    protected $rewindable = false;

    /**
     * Is first cross
     *
     * @var boolean
     */
    protected $firstCross = true;

    /**
     * Create a new cursor
     *
     * @param   Traversable|array   $data           Data to traverse
     * @param   Repository          $repository     Repository used for filter
     */
    public function __construct($data, Repository $repository, array $options, array $filter = [])
    {
        $this->data = $data;
        $this->repository = $repository;
        $this->objects = [];
        $this->options = $options;
        $this->filter = $filter;

        $this->generator = $this->createGenerator();
        $this->currentData = $this->generator->current();
    }

    /**
     * Set iterator to readOnly
     *
     * @return void
     */
    public function readOnly()
    {
        $this->readOnly = true;
    }

    /**
     * Rewinds the Iterator to the first element.
     *
     * @return void
     */
    public function rewind()
    {
        if (!$this->rewindable && !$this->firstCross) {
            throw new \Exception('Unable to traverse not rewindable iterator multiple time');
        }
        $this->firstCross = false;
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

        if ($this->currentData == null) {
            return false;
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
        if ($this->valid() && !isset($this->objects[$this->position])) {
            $object = $this->repository->createObject($this->currentData, $this->options);
            if ($this->rewindable) {
                $this->objects[] = $object;
            } else {
                return $object;
            }
        }

        if (isset($this->objects[$this->position])) {
            return $this->objects[$this->position];
        } else {
            return null;
        }
    }

    /**
     * Moves forward to next element.
     *
     * @return void
     */
    public function next()
    {
        $this->position++;
        $this->generator->next();
        $this->currentData = $this->generator->current();
    }

    /**
     * Create generator to traverse data
     *
     * @return Generator
     */
    protected function createGenerator()
    {
        foreach ($this->data as $data) {
            yield $data;
        }
    }

    /**
     * Set the iterator to be rewindable
     *
     * @return void
     */
    public function rewindable()
    {
        $this->rewindable = true;
    }

    /**
     * Count number of document from collection
     *
     * @return integer
     */
    public function count()
    {
        if (!isset($this->count)) {
            $this->count = $this->repository->count($this->filter);
        }
        return $this->count;
    }
}
