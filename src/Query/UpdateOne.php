<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;

class UpdateOne extends Query
{

    protected $document;

    protected $filters;

    protected $update;

    protected $options;

    protected $id;

    /**
     * Class metadata
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    public function __construct(DocumentManager $dm, Repository $repository, $document, $update = [], $options = [])
    {
        parent::__construct($dm, $repository, $document);
        $this->update = $update;
        $this->options = $options;
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
            $this->filters = ["_id" => $id];
        } elseif (is_object($this->document)) {
            throw new MappingException('Document sended to update function must be of type "' . $modelName . '"');
        } else {
            $queryCaster = $this->repository->getQueryCaster();
            $queryCaster->init($this->document);
            $this->filters = $queryCaster->getCastedQuery();
        }

        if (empty($this->update)) {
            $this->update = $this->repository->getUpdateQuery($this->document);
            if (!empty($this->update)) {
                $this->classMetadata->getEventManager()->execute(EventManager::EVENT_PRE_UPDATE, $this->document);
                $this->update = $this->repository->getUpdateQuery($this->document);
            }
        } else {
            $queryCaster = $this->repository->getQueryCaster();
            $queryCaster->init($this->update);
            $this->update = $queryCaster->getCastedQuery();
        }
    }

    public function perfomQuery(&$result)
    {
        if (!empty($this->update)) {
            $result = $this->repository->getCollection()->updateOne($this->filters, $this->update, $this->options);
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
                if ($this->dm->hasObject($this->document)) {
                    $this->dm->refresh($this->document);
                }
                if (null !== $this->document) {
                    $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_UPDATE, $this->document);
                    if ($this->dm->hasObject($this->document)) {
                        $this->repository->cacheObject($this->document);
                    }
                }
            }
        }
    }

    public function getFilters()
    {
        if (!isset($this->filters)) {
            $this->beforeQuery();
        }
        return $this->filters;
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
