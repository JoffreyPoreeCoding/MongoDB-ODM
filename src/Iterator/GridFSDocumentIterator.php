<?php

namespace JPC\MongoDB\ODM\Iterator;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Iterator\DocumentIterator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\EventManager;
use MongoDB\Driver\Cursor;

class GridFSDocumentIterator extends DocumentIterator {

    /**
     * Returns the current element.
     *
     * @return \PHPUnit_Framework_Test
     */
    public function current()
    {
        $this->currentData['stream'] = $this->repository->getBucket()->openDownloadStream($this->currentData['_id']);
        return parent::current();
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
}
