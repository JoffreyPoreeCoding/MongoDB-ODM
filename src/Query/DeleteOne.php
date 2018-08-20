<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;

class DeleteOne extends Query
{

    protected $document;

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
        parent::__construct($dm, $repository);
        $this->document = $document;
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_DELETE_ONE;
    }

    public function beforeQuery()
    {
        $this->classMetadata->getEventManager()->execute(EventManager::EVENT_PRE_DELETE, $this->document);
    }

    public function perfomQuery(&$result)
    {
        $filters = $this->getFilters();

        $result = $this->repository->getCollection()->deleteOne($filters, $this->options);

        if ($result->isAcknowledged()) {
            return true;
        } else {
            return false;
        }
    }

    public function afterQuery($result)
    {
        $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_DELETE, $this->document);
        $this->dm->removeObject($this->document);
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
