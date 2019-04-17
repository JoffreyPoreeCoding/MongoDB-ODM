<?php

namespace JPC\MongoDB\ODM;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FlushableCache;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Id\AbstractIdGenerator;
use JPC\MongoDB\ODM\Iterator\DocumentIterator;
use JPC\MongoDB\ODM\Query\BulkWrite;
use JPC\MongoDB\ODM\Query\DeleteOne;
use JPC\MongoDB\ODM\Query\InsertOne;
use JPC\MongoDB\ODM\Query\UpdateOne;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use MongoDB\Collection;
use JPC\MongoDB\ODM\Query\ReplaceOne;

/**
 * Allow to find, delete, document in MongoDB
 *
 * @author poree
 */
class Repository
{

    /**
     * Document manager
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * Class metadatas
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * Hydrator of model
     * @var Hydrator
     */
    protected $hydrator;

    /**
     * MongoDB collection
     * @var \MongoDB\Collection
     */
    protected $collection;

    /**
     * Cache for object changes
     * @var CacheProvider
     */
    protected $objectCache;

    /**
     * Model class name
     * @var string
     */
    protected $modelName;

    /**
     * Query caster
     * @var QueryCaster
     */
    protected $queryCaster;

    /**
     * Update query creator
     * @var UpdateQueryCreator
     */
    protected $updateQueryCreator;

    /**
     * Update query creator
     * @var CacheProvider
     */
    protected $lastProjectionCache;

    /**
     * Create a repository
     *
     * @param   DocumentManager     $documentManager    Document manager
     * @param   Collection          $collection         MongoDB Collection
     * @param   ClassMetadata       $classMetadata      Class metadata
     * @param   Hydrator            $hydrator           Object hydrator
     * @param   QueryCaster         $queryCaster        Query caster
     * @param   UpdateQueryCreator  $uqc                Update query Creator
     * @param   CacheProvider       $objectCache        Cache for persisted objects
     */
    public function __construct(DocumentManager $documentManager, Collection $collection, ClassMetadata $classMetadata, Hydrator $hydrator, QueryCaster $queryCaster, UpdateQueryCreator $uqc = null, CacheProvider $objectCache = null, CacheProvider $lastProjectionCache = null)
    {
        $this->documentManager = $documentManager;
        $this->collection = $collection;
        $this->classMetadata = $classMetadata;
        $this->hydrator = $hydrator;

        $this->modelName = $classMetadata->getName();
        $this->objectCache = isset($objectCache) ? $objectCache : new ArrayCache();

        $this->lastProjectionCache = isset($lastProjectionCache) ? $lastProjectionCache : new ArrayCache();
        $this->lastProjectionCache->setNamespace('obj_projection');

        $this->queryCaster = $queryCaster;
        $this->updateQueryCreator = isset($uqc) ? $uqc : new UpdateQueryCreator();
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clear()
    {
        if (is_a($this->objectCache, FlushableCache::class)) {
            $this->objectCache->flushAll();
        }
    }

    /**
     * Count corresponding documents for filters
     *
     * @param   array                   $filters            Object
     * @param   array                   $options            Options for the query
     * @return  int                                         Number of corresponding documents
     */
    public function count($filters = [], $options = [])
    {
        return $this->collection->count($this->castQuery($filters), $options);
    }

    /**
     * Get distinct value for a field
     *
     * @param  string $fieldName Name of the field
     * @param  array  $filters   Filters of query
     * @param  array  $options   Options of query
     * @return array             List of distinct values
     */
    public function distinct($fieldName, $filters = [], $options = [])
    {
        $field = $fieldName;

        $propInfos = $this->classMetadata->getPropertyInfoForField($fieldName);
        if (!$propInfos) {
            $propInfos = $this->classMetadata->getPropertyInfo($fieldName);
        }

        if (isset($propInfos)) {
            $field = $propInfos->getField();

            if ($propInfos->getMetadata()) {
                $field = "metadata." . $field;
            }
        }

        $filters = $this->castQuery($filters);

        $result = $this->collection->distinct($field, $filters, $options);
        return $result;
    }

    /**
     * Find document by ID
     *
     * Options :
     *  *   readOnly : boolean - When false, flush will not update object
     * @see MongoDB\Operation\FindOne::__construct for more option
     *
     * @param   mixed                   $id                 Id of the document
     * @param   array                   $projections        Projection of the query
     * @param   array                   $options            Options for the query
     * @return  object|null
     */
    public function find($id, $projections = [], $options = [])
    {
        $options = $this->createOption($projections, null, $options);

        $result = $this->collection->findOne(["_id" => $id], $options);

        return $this->createObject($result, $options);
    }

    /**
     * Find all document of the collection
     *
     * Options :
     *  *   readOnly : boolean - When false, flush will not update object
     *  *   iterator : boolean|string - Return DocumentIterator if true (or specified class if is string)
     * @see MongoDB\Operation\Find::__construct for more option
     *
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findAll($projections = [], $sorts = [], $options = [])
    {
        $options = $this->createOption($projections, $sorts, $options);

        $result = $this->collection->find([], $options);

        if (!isset($options['iterator']) || $options['iterator'] === false) {
            $objects = [];
            foreach ($result as $datas) {
                if (null != ($object = $this->createObject($datas, $options))) {
                    $objects[] = $object;
                }
            }
            return $objects;
        } else {
            $iteratorClass = $options['iterator'];
            $iterator = $iteratorClass === true ? new DocumentIterator($result, $this->modelName, $this) : new $iteratorClass($result, $this->modelName, $this);
            if (isset($options['readOnly']) && $options['readOnly'] == true) {
                $iterator->readOnly();
            }
            return $iterator;
        }
    }

    /**
     * Get documents which match the query
     *
     * Options :
     *  *   readOnly : boolean - When false, flush will not update object
     *  *   iterator : boolean|string - Return DocumentIterator if true (or specified class if is string)
     * @see MongoDB\Operation\Find::__construct for more option
     *
     * @param   array                   $filters            Filters
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sorts specification
     * @param   array                   $options            Options for the query
     * @param array $options
     * @return void
     */
    public function findBy($filters, $projections = [], $sorts = [], $options = [])
    {
        $options = $this->createOption($projections, $sorts, $options);

        $filters = $this->castQuery($filters);

        $result = $this->collection->find($filters, $options);
        if (!isset($options['iterator']) || $options['iterator'] == false) {
            $objects = [];
            foreach ($result as $datas) {
                if (null != ($object = $this->createObject($datas, $options))) {
                    $objects[] = $object;
                }
            }
            return $objects;
        } else {
            $iteratorClass = $options['iterator'];
            $iterator = $iteratorClass === true ? new DocumentIterator($result, $this->modelName, $this, $filters) : new $iteratorClass($result, $this->modelName, $this, $filters);
            if (isset($options['readOnly']) && $options['readOnly'] == true) {
                $iterator->readOnly();
            }
            return $iterator;
        }
    }

    /**
     * Get first document which match the query
     *
     * Options :
     *  *   readOnly : boolean - When false, flush will not update object
     * @see MongoDB\Operation\Find::__construct for more option
     *
     * @param   array                   $filters            Filters
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sorts specification
     * @param   array                   $options            Options for the query
     * @param array $options
     * @return void
     */
    public function findOneBy($filters = [], $projections = [], $sorts = [], $options = [])
    {
        $options = $this->createOption($projections, $sorts, $options);

        $filters = $this->castQuery($filters);

        $result = $this->collection->findOne($filters, $options);

        return $this->createObject($result, $options);
    }

    /**
     * Find a document and make specified update on it
     *
     * Options :
     *  *   readOnly : boolean - When false, flush will not update object
     * @see MongoDB\Operation\FindAndModify::__construct for more option
     *
     * @param   array                   $filters            Filters
     * @param   array                   $update             Update to perform
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sorts specification
     * @param   array                   $options            Options for the query
     * @param array $options
     * @return void
     */
    public function findAndModifyOneBy($filters = [], $update = [], $projections = [], $sorts = [], $options = [])
    {

        $options = $this->createOption($projections, $sorts, $options);

        $filters = $this->castQuery($filters);
        $update = $this->castQuery($update);

        $result = (array) $this->collection->findOneAndUpdate($filters, $update, $options);

        return $this->createObject($result, $options);
    }

    /**
     * Get tailable cursor for query
     *
     * @param  array  $filters Filters of query
     * @param  array  $options Option (Tailable setted as default)
     * @return \MongoDB\Driver\TailableCursor          A tailable cursor
     */
    public function getTailableCursor($filters = [], $options = [])
    {
        $options['cursorType'] = \MongoDB\Operation\Find::TAILABLE_AWAIT;

        return $this->collection->find($this->castQuery($filters), $options);
    }

    /**
     * Insert a document in collection
     *
     * @param   mixed   $document   Document to insert
     * @param   array   $options    Options
     * @return  void
     */
    public function insertOne($document, $options = [])
    {
        $query = new InsertOne($this->documentManager, $this, $document, $options);
        if (isset($options['getQuery']) && $options['getQuery']) {
            return $query;
        } else {
            return $query->execute();
        }
    }

    /**
     * Insert multiple documents in collection
     *
     * @param   mixed   $documents  Documents to insert
     * @param   array   $options    Options
     * @return  void
     */
    public function insertMany($documents, $options = [])
    {
        $insertQuery = [];
        foreach ($documents as $document) {
            $this->classMetadata->getEventManager()->execute(EventManager::EVENT_PRE_INSERT, $document);
            $query = $this->hydrator->unhydrate($document);

            $idGen = $this->classMetadata->getIdGenerator();
            if ($idGen !== null) {
                if (!class_exists($idGen) || !is_subclass_of($idGen, AbstractIdGenerator::class)) {
                    throw new \Exception('Bad ID generator : class \'' . $idGen . '\' not exists or not extends JPC\MongoDB\ODM\Id\AbstractIdGenerator');
                }
                $generator = new $idGen();
                $query['_id'] = $generator->generate($this->documentManager, $document);
            }

            $insertQuery[] = $query;
        }

        $result = $this->collection->insertMany($insertQuery, $options);

        if ($result->isAcknowledged()) {
            foreach ($result->getInsertedIds() as $key => $id) {
                if ($id instanceof \stdClass) {
                    $id = (array) $id;
                }
                $insertQuery[$key]["_id"] = $id;
                $this->hydrator->hydrate($documents[$key], $insertQuery[$key]);

                $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_INSERT, $documents[$key]);

                $this->cacheObject($documents[$key]);
            }

            return true;
        } else {
            foreach ($documents as $document) {
                $this->cacheObject($document);
                // $this->documentManager->removeObject();
            }
            return false;
        }
    }

    /**
     * Update a document in mongoDB
     *
     * @param   mixed   $document   Document or query to update
     * @param   array   $update     Update query, let empty to update document based on object changes
     * @param   array   $options    Options
     * @return  void
     */
    public function updateOne($document, $update = [], $options = [])
    {
        $query = new UpdateOne($this->documentManager, $this, $document, $update, $options);
        if (isset($options['getQuery']) && $options['getQuery']) {
            return $query;
        } else {
            return $query->execute();
        }
    }

    /**
     * Update many document
     *
     * @param   array   $filters    Filters
     * @param   array   $update     Update to perform
     * @param   array   $options    Options
     * @return  void
     */
    public function updateMany($filters, $update, $options = [])
    {
        $result = $this->collection->updateMany($this->castQuery($filters), $update, $options);

        if ($result->isAcknowledged()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update a document in mongoDB
     *
     * @param   mixed   $document   Document or query to update
     * @param   array   $update     Update query, let empty to update document based on object changes
     * @param   array   $options    Options
     * @return  void
     */
    public function replaceOne($document, $replacement, $options = [])
    {
        $query = new ReplaceOne($this->documentManager, $this, $document, $replacement, $options);
        if (isset($options['getQuery']) && $options['getQuery']) {
            return $query;
        } else {
            return $query->execute();
        }
    }

    /**
     * Delete a document
     *
     * @param   object|array    $document   Document to delete
     * @param   array           $options    Options
     * @return  void
     */
    public function deleteOne($document, $options = [])
    {
        $query = new DeleteOne($this->documentManager, $this, $document, $options);
        if (isset($options['getQuery']) && $options['getQuery']) {
            return $query;
        } else {
            return $query->execute();
        }
    }

    /**
     * Delete many document
     *
     * @param   array   $filter     Filter wich match to objects to delete
     * @param   array   $options    Options
     * @return  void
     */
    public function deleteMany($filter, $options = [])
    {
        $filter = $this->castQuery($filter);
        $result = $this->collection->deleteMany($filter, $options);

        if ($result->isAcknowledged()) {
            return true;
        } else {
            return false;
        }
    }

    public function createBulkWriteQuery()
    {
        return new BulkWrite($this->documentManager, $this);
    }

    /**
     * Create object based on provided data
     *
     * @param   array   $data       Array of data which will hydrate object
     * @param   array   $options    Options
     * @return  void
     */
    protected function createObject($data, $options = [])
    {
        $object = null;
        if ($data != null) {
            $id = isset($data['_id']) ? serialize($data['_id']) . $this->getCollection() : null;
            $model = $this->getModelName();

            $softHydrate = false;
            if (null !== $this->documentManager->getObject($id)) {
                $softHydrate = true;
                $object = $this->documentManager->getObject($id);
            } else {
                $object = new $this->modelName();
            }

            $this->hydrator->hydrate($object, $data, $softHydrate);

            $this->classMetadata->getEventManager()->execute(EventManager::EVENT_POST_LOAD, $object);
            if (!isset($options['readOnly']) || $options['readOnly'] != true) {
                $oid = spl_object_hash($object);
                $data = $this->hydrator->unhydrate($object);
                $id = isset($data['_id']) ? serialize($data['_id']) . $this->getCollection() : $oid;
                $projection = isset($options['projection']) ? $options['projection'] : [];
                $this->lastProjectionCache->save($id, $projection);
                $this->cacheObject($object);
                $this->documentManager->addObject($object, DocumentManager::OBJ_MANAGED, $this);
            }
            return $object;
        }
        return $object;
    }

    /**
     * Create options based on parameters
     *
     * @param   array   $projections    Projection specification
     * @param   array   $sort           Sort specification
     * @param   array   $otherOptions   Other options
     * @return  void
     */
    protected function createOption($projections, $sort, $otherOptions = [])
    {
        $options = [];
        isset($projections) ? $options["projection"] = $this->castQuery($projections) : null;
        isset($sort) ? $options["sort"] = $this->castQuery($sort) : null;

        $options = array_merge($this->documentManager->getDefaultOptions(), $otherOptions, $options);
        return $options;
    }

    /**
     * Drop the bucket from database
     *
     * @return void
     */
    public function drop()
    {
        $result = (array) $this->collection->drop();

        if ($result['ok']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cast the query
     *
     * @param   array   $query  Query to cast
     * @return  void
     */
    protected function castQuery($query)
    {
        $this->queryCaster->init($query);
        return $this->queryCaster->getCastedQuery();
    }

    /**
     * Store object in cache to see changes
     *
     * @param   object  $object Object to cache
     * @return  void
     */
    public function cacheObject($object)
    {
        if (is_object($object)) {
            $this->objectCache->save(spl_object_hash($object), $this->hydrator->unhydrate($object), 120);
        }
    }

    /**
     * Check if document has update
     *
     * @param mixed $object
     * @return boolean
     */
    public function hasUpdate($object)
    {
        return !empty($this->getUpdateQuery($object));
    }

    /**
     * Get the cached document
     *
     * @param   object  $object     Object to uncache
     * @return  object
     */
    protected function uncacheObject($object)
    {
        return $this->objectCache->fetch(spl_object_hash($object));
    }

    public function removeObjectCache($object){
        return $this->objectCache->delete(spl_object_hash($object))
    }

    /**
     * Create the update query from object diff
     *
     * @param   object  $document   The document that the update query will match
     * @return  array
     */
    public function getUpdateQuery($document)
    {
        $updateQuery = [];
        $old = $this->uncacheObject($document);
        $new = $this->hydrator->unhydrate($document);

        $old = !$old ? [] : $old;
        $query = $this->updateQueryCreator->createUpdateQuery($old, $new);

        return $query;
    }

    /**
     * Gets the Document manager.
     *
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * Sets the Document manager.
     *
     * @param DocumentManager $documentManager the document manager
     *
     * @return self
     */
    public function setDocumentManager(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;

        return $this;
    }

    /**
     * Gets the Class metadatas.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * Sets the Class metadatas.
     *
     * @param ClassMetadata $classMetadata the class metadata
     *
     * @return self
     */
    public function setClassMetadata(ClassMetadata $classMetadata)
    {
        $this->classMetadata = $classMetadata;

        return $this;
    }

    /**
     * Gets the Hydrator of model.
     *
     * @return Hydrator
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    /**
     * Sets the Hydrator of model.
     *
     * @param Hydrator $hydrator the hydrator
     *
     * @return self
     */
    public function setHydrator(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * Gets the MongoDB collection.
     *
     * @return \MongoDB\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Sets the MongoDB collection.
     *
     * @param \MongoDB\Collection $collection the collection
     *
     * @return self
     */
    public function setCollection(\MongoDB\Collection $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Gets the Cache for object changes.
     *
     * @return ApcuCache
     */
    public function getObjectCache()
    {
        return $this->objectCache;
    }

    /**
     * Sets the Cache for object changes.
     *
     * @param ApcuCache $objectCache the object cache
     *
     * @return self
     */
    public function setObjectCache(CacheProvider $objectCache)
    {
        $this->objectCache = $objectCache;

        return $this;
    }

    /**
     * Gets the Model class name.
     *
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Sets the Model class name.
     *
     * @param string $modelName the model name
     *
     * @return self
     */
    public function setModelName($modelName)
    {
        $this->modelName = $modelName;

        return $this;
    }

    /**
     * Gets the Query caster.
     *
     * @return QueryCaster
     */
    public function getQueryCaster()
    {
        return $this->queryCaster;
    }

    /**
     * Sets the Query caster.
     *
     * @param QueryCaster $queryCaster the query caster
     *
     * @return self
     */
    public function setQueryCaster(QueryCaster $queryCaster)
    {
        $this->queryCaster = $queryCaster;

        return $this;
    }

    /**
     * Gets the Update query creator.
     *
     * @return UpdateQueryCreator
     */
    public function getUpdateQueryCreator()
    {
        return $this->updateQueryCreator;
    }

    /**
     * Sets the Update query creator.
     *
     * @param UpdateQueryCreator $updateQueryCreator the update query creator
     *
     * @return self
     */
    public function setUpdateQueryCreator(UpdateQueryCreator $updateQueryCreator)
    {
        $this->updateQueryCreator = $updateQueryCreator;

        return $this;
    }

    public function getLastProjection($object)
    {
        $oid = spl_object_hash($object);
        $data = $this->hydrator->unhydrate($object);
        $id = $data["_id"];
        $cacheId = isset($data['_id']) ? serialize($data['_id']) . $this->getCollection() : $oid;
        $projection = $this->lastProjectionCache->fetch($cacheId);
        return $projection !== false ? $projection : [];
    }
}
