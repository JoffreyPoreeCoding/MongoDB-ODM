<?php

namespace JPC\MongoDB\ODM\Query;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Query\Query;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;

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

    public function __construct(DocumentManager $dm, Repository $repository, $document, $options = [])
    {
        parent::__construct($dm, $repository);
        $this->document = $document;
        $this->options = $options;
        $this->classMetadata = $repository->getClassMetadata();
    }

    public function getType()
    {
        return self::TYPE_INSERT_ONE;
    }

    public function beforeQuery()
    {
        $this->classMetadata->getEventManager()->execute(EventManager::EVENT_PRE_INSERT, $this->document);

        $idGen = $this->classMetadata->getIdGenerator();
        if ($idGen !== null) {
            if (!class_exists($idGen) || !is_subclass_of($idGen, AbstractIdGenerator::class)) {
                throw new \Exception('Bad ID generator : class \'' . $idGen . '\' not exists or not extends JPC\MongoDB\ODM\Id\AbstractIdGenerator');
            }
            $generator = new $idGen();
            $this->id = $generator->generate($this->documentManager, $this->document);
        }
    }

    public function perfomQuery(&$result)
    {
        $insertQuery = $this->getDocument();

        $result = $this->repository->getCollection()->insertOne($insertQuery, $this->options);

        if ($result->isAcknowledged()) {
            $id = $result->getInsertedId();
            if ($id instanceof \stdClass) {
                $id = (array) $id;
            }

            $result = ['id' => $id];

            return true;
        } else {
            return false;
        }
    }

    public function afterQuery($result)
    {
        $modelName = $this->repository->getModelName();
        if ($this->document instanceof $modelName) {
            $this->repository->getHydrator()->hydrate($this->document, ['_id' => $result['id']], true);
            $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_INSERT, $this->document);
            $this->repository->cacheObject($this->document);
        }
    }

    public function getDocument()
    {
        $document = $this->repository->getHydrator()->unhydrate($this->document);

        if ($this->id !== null) {
            $document['_id'] = $this->id;
        }

        return $document;
    }
}
