<?php

namespace JPC\MongoDB\ODM\Iterator;

use JPC\MongoDB\ODM\Iterator\DocumentIterator;
use JPC\MongoDB\ODM\Repository;

/**
 * Iterator for GridFS entry
 */
class GridFSDocumentIterator extends DocumentIterator
{
    /**
     * @var Bucket
     */
    private $bucket;

    /**
     * Create a new cursor
     *
     * @param   Traversable|array   $data           Data to traverse
     * @param   Repository          $repository     Repository used for filter
     */
    public function __construct($data, Repository $repository, array $options, array $filter = [])
    {
        parent::__construct($data, $repository, $options, $filter);

        $this->bucket = $this->repository->getBucket();
    }

    /**
     * Returns the current element.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        if (!isset($this->options['noStream']) || !$this->options['noStream']) {
            $this->currentData['stream'] = $this->bucket->openDownloadStream($this->currentData['_id']);
        }
        return parent::current();
    }

    /**
     * Moves forward to next element.
     *
     * @return void
     */
    public function next(): void
    {
        $this->position++;
        $this->generator->next();
        $this->currentData = $this->generator->current();
    }
}
