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
     * Returns the current element.
     *
     * @return mixed
     */
    public function current()
    {
        $this->currentData['stream'] = $this->repository->getBucket()->openDownloadStream($this->currentData['_id']);
        return parent::current();
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
}
