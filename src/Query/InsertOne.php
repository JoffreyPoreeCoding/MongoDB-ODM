<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Event\ModelEvent\PostInsertEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreInsertEvent;
use JPC\MongoDB\ODM\Id\AbstractIdGenerator;
use JPC\MongoDB\ODM\Id\AutoGenerator;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;

class InsertOne extends Query
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

    public function __construct(DocumentManager $documentManager, Repository $repository, $document, $options = [])
    {
        parent::__construct($documentManager, $repository, $document);
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_INSERT_ONE;
    }

    public function beforeQuery()
    {
        $event = new PreInsertEvent($this->documentManager, $this->repository, $this->document);
        $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);

        $idGen = $this->classMetadata->getIdGenerator();
        if ($idGen !== null) {
            if (!class_exists($idGen) || !is_subclass_of($idGen, AbstractIdGenerator::class)) {
                throw new \Exception('Bad ID generator : class \'' . $idGen . '\' not exists or not extends JPC\MongoDB\ODM\Id\AbstractIdGenerator');
            }
            $generator = new $idGen();
        } else {
            $generator = new AutoGenerator();
        }

        $this->id = $generator->generate($this->documentManager, $this->document);
    }

    public function performQuery(&$result)
    {
        $insertQuery = $this->getDocument();

        $queryResult = $this->repository->getCollection()->insertOne($insertQuery, $this->options);
        $result = $queryResult;

        if ($queryResult->isAcknowledged()) {
            $id = $queryResult->getInsertedId();
            if ($id instanceof \stdClass) {
                $id = (array) $id;
            }

            $result = ['id' => $id];
        }

        return $queryResult->isAcknowledged() || $this->repository->getCollection()->getWriteConcern()->getW() === 0;
    }

    public function afterQuery($result)
    {
        $modelName = $this->repository->getModelName();
        if ($this->document instanceof $modelName) {
            if (isset($result['_id'])) {
                if ($result['id'] instanceof \stdClass) {
                    $result['id'] = (array) $result['id'];
                }
                $this->repository->getHydrator()->hydrate($this->document, ['_id' => $result['id']], true);
            }

            $event = new PostInsertEvent($this->documentManager, $this->repository, $this->document);
            $this->documentManager->getEventDispatcher()->dispatch($event, $event::NAME);
            if ($this->documentManager->hasObject($this->document)) {
                $this->repository->cacheObject($this->document);
                $this->documentManager->setObjectState($this->document, ObjectManager::OBJ_MANAGED);
            }
        }
    }

    public function getDocument()
    {
        $document = $this->repository->getHydrator()->unhydrate($this->document);

        if ($this->id !== null && !isset($document['_id'])) {
            $document['_id'] = $this->id;
            $this->repository->getHydrator()->hydrate($this->document, $document);
        }

        return $document;
    }
}
