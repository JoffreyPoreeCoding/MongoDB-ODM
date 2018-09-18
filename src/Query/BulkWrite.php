<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;

class BulkWrite extends Query
{

    protected $queries;

    public function __construct(DocumentManager $dm, Repository $repository, array $queries = [])
    {
        parent::__construct($dm, $repository, null);
        $this->queries = $queries;
    }

    public function addQuery(Query $query)
    {
        if (defined('BLA')) {
            dump($this->repository->getCollection()->getCollectionName());
            dump(get_class($query));
            dump($query->getDocument()['_id']);
        }
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

    public function perfomQuery(&$result)
    {
        $operations = [];
        foreach ($this->queries as $query) {
            switch ($query->getType()) {
                case self::TYPE_INSERT_ONE:
                    $operations[] = [$query->getType() => [$query->getDocument()]];
                    break;
                case self::TYPE_UPDATE_ONE:
                    $operations[] = [$query->getType() => [$query->getFilters(), $query->getUpdate(), $query->getOptions()]];
                    break;
                case self::TYPE_DELETE_ONE:
                    $operations[] = [$query->getType() => [$query->getFilters(), $query->getOptions()]];
                    break;
                default:
                    throw new \Exception('Not supported operation type \'' . $query->getType() . '\'');
            }
        }

        $result = $this->repository->getCollection()->bulkWrite($operations);
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
}
