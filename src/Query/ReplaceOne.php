<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;

class ReplaceOne extends Query
{

    protected $document;

    protected $filters;

    protected $replacement;

    protected $options;

    protected $id;

    /**
     * Class metadata
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    public function __construct(DocumentManager $dm, Repository $repository, $document, $replacement = [], $options = [])
    {
        parent::__construct($dm, $repository, $document);
        $this->replacement = $replacement;
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_REPLACE_ONE;
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

        if (is_object($this->replacement) && $this->replacement instanceof $modelName) {
            $unhydratedObject = $this->repository->getHydrator()->unhydrate($this->replacement);
            $this->replacement = $unhydratedObject;
        } elseif (is_object($this->replacement)) {
            throw new MappingException('Replacement sended to update function must be of type "' . $modelName . '"');
        } else {
            $queryCaster = $this->repository->getQueryCaster();
            $queryCaster->init($this->replacement);
            $this->replacement = $queryCaster->getCastedQuery();
        }
    }

    public function perfomQuery(&$result)
    {
        if (!empty($this->replacement)) {
            $result = $this->repository->getCollection()->replaceOne($this->filters, $this->replacement, $this->options);
        } else {
            return true;
        }
        return $result->isAcknowledged() || $this->repository->getCollection()->getWriteConcern()->getW() === 0;
    }

    public function afterQuery($result)
    {
        if (!empty($this->replacement)) {
            $modelName = $this->repository->getModelName();
            if ($this->document instanceof $modelName) {
                $this->dm->refresh($this->document);
                $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_UPDATE, $this->document);
                $this->repository->cacheObject($this->document);
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

    public function getReplacement()
    {
        if (!isset($this->replacement)) {
            $this->beforeQuery();
        }
        return $this->replacement;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
