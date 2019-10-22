<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\ModelEvent\PreDeleteEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostDeleteEvent;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

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

    public function __construct(DocumentManager $documentManager, Repository $repository, $document, $options = [])
    {
        parent::__construct($documentManager, $repository, $document);
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_DELETE_ONE;
    }

    public function beforeQuery()
    {
        if (is_a($this->document, $this->repository->getModelName())) {
            $event = new PreDeleteEvent($this->documentManager, $this->repository, $this->document);
            $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);
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
        if (is_a($this->document, $this->repository->getModelName())) {
            $event = new PostDeleteEvent($this->documentManager, $this->repository, $this->document);
            $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);
            if (is_object($this->document) && $this->documentManager->hasObject($this->document)) {
                $this->documentManager->removeObject($this->document);
                $this->repository->removeObjectCache($this->document);
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
