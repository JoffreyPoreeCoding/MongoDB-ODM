<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;

class DeleteOne extends Query
{

    protected $options;

    protected $id;

    /**
     * Class metadata
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    public function __construct(DocumentManager $dm, Repository $repository, $document, $options = [])
    {
        parent::__construct($dm, $repository, $document);
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_DELETE_ONE;
    }

    public function beforeQuery()
    {
        if (is_subclass_of($this->document, $this->repository->getModelName())) {
            $this->classMetadata->getEventManager()->execute(EventManager::EVENT_PRE_DELETE, $this->document);
        }
    }

    public function perfomQuery(&$result)
    {
        $filters = $this->getFilters();

        $result = $this->repository->getCollection()->deleteOne($filters, $this->options);

        return $result->isAcknowledged() || $this->repository->getCollection()->getWriteConcern()->getW() === 0;
    }

    public function afterQuery($result)
    {
        if (is_subclass_of($this->document, $this->repository->getModelName())) {
            $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_DELETE, $this->document);
            if (is_object($this->document) && $this->dm->hasObject($this->document)) {
                $this->dm->removeObject($this->document);
            }
        }
    }

    public function getFilters()
    {
        $unhydratedObject = $this->repository->getHydrator()->unhydrate($this->document);
        $id = $unhydratedObject["_id"];

        return ['_id' => $id];
    }

    public function getOptions()
    {
        return $this->options;
    }
}
