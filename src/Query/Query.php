<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Repository;

abstract class Query
{
    const TYPE_INSERT_ONE = 'insertOne';
    const TYPE_UPDATE_ONE = 'updateOne';
    const TYPE_REPLACE_ONE = 'replaceOne';
    const TYPE_DELETE_ONE = 'deleteOne';
    const TYPE_DELETE_MANY = 'deleteMany';
    const TYPE_BULK_WRITE = 'bulkWrite';

    /**
     * Document manager
     *
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * Repository
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Document where to perfom
     *
     * @var array|object
     */
    protected $document;

    /**
     * @var array
     *
     * @var array
     */
    protected $options;

    /**
     * @var mixed
     */
    protected $rawResult;

    protected $beforeQueryExecuted = false;
    protected $afterQueryExecuted = false;

    public function __construct(DocumentManager $documentManager, Repository $repository, $document, $options = [])
    {
        $this->documentManager = $documentManager;
        $this->repository = $repository;
        $this->document = $document;
        $this->options = $options;
    }

    abstract public function getType();

    abstract public function beforeQuery();

    abstract public function performQuery(&$result);

    abstract public function afterQuery($result);

    public function execute()
    {
        $result = [];

        if (!$this->beforeQueryExecuted) {
            $this->beforeQuery();
            $this->beforeQueryExecuted = true;
        }

        if ($acknowledge = $this->performQuery($result)) {
            if (!$this->afterQueryExecuted) {
                $this->afterQuery($result);
                $this->afterQueryExecuted = true;
            }
        }

        return ($this->options['rawResult'] ?? false) ? $this->rawResult : $acknowledge;
    }

    public function reset()
    {
        $this->beforeQueryExecuted = false;
        $this->afterQueryExecuted = false;
    }

    /**
     * Get the value of rawResult
     *
     * @return  mixed
     */
    public function getRawResult()
    {
        return $this->rawResult;
    }
}
