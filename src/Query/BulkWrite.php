<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\BeforeQueryEvent;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;

class BulkWrite extends Query
{

    protected $queries;

    protected $options;

    public function __construct(DocumentManager $documentManager, Repository $repository, array $queries = [], array $options = [])
    {
        parent::__construct($documentManager, $repository, null);
        $this->queries = $queries;
        $this->options = $options;
    }

    public function addQuery(Query $query)
    {
        $this->queries[] = $query;
    }

    public function getType()
    {
        return self::TYPE_BULK_WRITE;
    }

    public function beforeQuery()
    {
        foreach ($this->queries as $query) {
            $query->beforeQuery();
        }
    }

    public function performQuery(&$result)
    {
        $operations = [];
        foreach ($this->queries as $query) {
            $event = new BeforeQueryEvent($this->documentManager, $this->repository, $query);
            $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);
            
            switch ($query->getType()) {
                case self::TYPE_INSERT_ONE:
                    $operations[] = [$query->getType() => [$query->getDocument()]];
                    break;
                case self::TYPE_UPDATE_ONE:
                    $operations[] = [$query->getType() => [$query->getFilter(), $query->getUpdate(), $query->getOptions()]];
                    break;
                case self::TYPE_DELETE_ONE:
                    $operations[] = [$query->getType() => [$query->getFilter(), $query->getOptions()]];
                    break;
                case self::TYPE_DELETE_MANY:
                    $operations[] = [$query->getType() => [$query->getFilter(), $query->getOptions()]];
                    break;
                case self::TYPE_REPLACE_ONE:
                    $operations[] = [$query->getType() => [$query->getFilter(), $query->getReplacement(), $query->getOptions()]];
                    break;
                default:
                    throw new \Exception('Not supported operation type \'' . $query->getType() . '\'');
            }
        }

        if (empty($operations)) {
            return true;
        }

        $result = $this->repository->getCollection()->bulkWrite($operations, $this->options);
        return $result->isAcknowledged() || $this->repository->getCollection()->getWriteConcern()->getW() === 0;
    }

    public function afterQuery($result)
    {
        foreach ($this->queries as $i => $query) {
            switch ($query->getType()) {
                case self::TYPE_INSERT_ONE:
                    $innerResult = [
                        'id' => $result->getInsertedIds()[$i],
                    ];
                    $query->afterQuery($innerResult);
                    break;
                case self::TYPE_UPDATE_ONE:
                    $query->afterQuery([]);
                    break;
                case self::TYPE_DELETE_ONE:
                    $query->afterQuery([]);
                    break;
            }
        }
    }

    /**
     * Get the value of options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the value of options
     *
     * @return  self
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
