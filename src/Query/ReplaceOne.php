<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\ModelEvent\PostUpdateEvent;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\Query\FilterableQuery;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class ReplaceOne extends Query
{
    use FilterableQuery;

    protected $document;

    protected $replacement;

    protected $options;

    protected $id;

    /**
     * Class metadata
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    public function __construct(DocumentManager $documentManager, Repository $repository, $document, $replacement = [], $options = [])
    {
        parent::__construct($documentManager, $repository, $document);
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
            $this->filter = ["_id" => $id];
        } elseif (is_object($this->document)) {
            throw new MappingException('Document sended to update function must be of type "' . $modelName . '"');
        } else {
            $queryCaster = $this->repository->getQueryCaster();
            $queryCaster->init($this->document);
            $this->filter = $queryCaster->getCastedQuery();
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

    public function performQuery(&$result)
    {
        if (!empty($this->replacement)) {
            $result = $this->repository->getCollection()->replaceOne($this->filter, $this->replacement, $this->options);
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
                if ($this->documentManager->hasObject($this->document)) {
                    $this->documentManager->refresh($this->document);
                }
                $event = new PostUpdateEvent($this->documentManager, $this->repository, $this->document);
                $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);
                if ($this->documentManager->hasObject($this->document)) {
                    $this->repository->cacheObject($this->document);
                }
            }
        }
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
