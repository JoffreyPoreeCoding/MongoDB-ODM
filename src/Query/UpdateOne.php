<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\ModelEvent\PostUpdateEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreUpdateEvent;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\Query\FilterableQuery;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class UpdateOne extends Query
{
    use FilterableQuery;

    protected $document;

    protected $update;

    protected $options;

    protected $id;

    /**
     * Class metadata
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    public function __construct(DocumentManager $documentManager, Repository $repository, $document, $update = [], $options = [])
    {
        parent::__construct($documentManager, $repository, $document, $options);
        $this->update = $update;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_UPDATE_ONE;
    }

    public function beforeQuery()
    {
        $modelName = $this->repository->getModelName();
        if (is_object($this->document) && $this->document instanceof $modelName) {
            $unhydratedObject = $this->repository->getHydrator()->unhydrate($this->document);
            $id = $unhydratedObject["_id"];
            $this->filter = ["_id" => $id];
        } elseif (is_object($this->document)) {
            throw new MappingException('Document sended to update function must be of type "' . $modelName . '"');
        } else {
            $queryCaster = $this->repository->getQueryCaster();
            $queryCaster->init($this->document);
            $this->filter = $queryCaster->getCastedQuery();
        }

        if (empty($this->update) || $this->document === $this->update) {
            $event = new PreUpdateEvent($this->documentManager, $this->repository, $this->document);
            $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);

            $this->update = $this->repository->getUpdateQuery($this->document, $this->options);
        } else {
            $modelName = $this->repository->getModelName();
            if ($this->update instanceof $modelName) {
                $unhydrated = $this->repository->getHydrator()->unhydrate($this->update);
                unset($unhydrated['_id']);
                $this->update = ['$set' => $unhydrated];
            }
            $queryCaster = $this->repository->getQueryCaster();
            $queryCaster->init($this->update);
            $this->update = $queryCaster->getCastedQuery();
        }
    }

    public function performQuery(&$result)
    {
        if (!empty($this->update)) {
            $result = $this->repository->getCollection()->updateOne($this->filter, $this->update, $this->options);
            $this->rawResult = $result;
        } else {
            return true;
        }
        return $result->isAcknowledged() || $this->repository->getCollection()->getWriteConcern()->getW() === 0;
    }

    public function afterQuery($result)
    {
        if (!empty($this->update)) {
            $modelName = $this->repository->getModelName();
            if ($this->document instanceof $modelName) {
                if ($this->documentManager->hasObject($this->document)) {
                    $this->documentManager->refresh($this->document);
                }
                if (null !== $this->document) {
                    $event = new PostUpdateEvent($this->documentManager, $this->repository, $this->document);
                    $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);
                    if ($this->documentManager->hasObject($this->document)) {
                        $this->repository->cacheObject($this->document);
                    }
                }
            }
        }
    }

    public function getUpdate()
    {
        if (!isset($this->update)) {
            $this->beforeQuery();
        }
        return $this->update;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
