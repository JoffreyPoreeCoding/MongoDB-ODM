<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\ModelEvent\PostDeleteEvent;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class DeleteMany extends Query
{

    use FilterableQuery;

    protected $options;

    protected $id;

    /**
     * Class metadata
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    public function __construct(DocumentManager $documentManager, Repository $repository, $document, $options = [])
    {
        parent::__construct($documentManager, $repository, $document);
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_DELETE_MANY;
    }

    public function beforeQuery()
    {
        $queryCaster = $this->repository->getQueryCaster();
        $queryCaster->init($this->document);
        $this->filter = $queryCaster->getCastedQuery();
    }

    public function performQuery(&$result)
    {
        $result = $this->repository->getCollection()->deleteMany($this->filter, $this->options);
        return $result->isAcknowledged() || $this->repository->getCollection()->getWriteConcern()->getW() === 0;
    }

    public function afterQuery($result)
    {
        //Nothing todo on deleteMany
    }

    public function getOptions()
    {
        return $this->options;
    }
}
