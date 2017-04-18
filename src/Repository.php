<?php

namespace JPC\MongoDB\ODM;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\Factory\RepositoryFactory;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use MongoDB\Collection;

/**
 * Allow to find, delete, document in MongoDB
 *
 * @author poree
 */
class Repository {

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
     * @var ApcuCache 
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
     * Create new Repository
     * 
     * @param   Tools\ClassMetadata     $classMetadata      Metadata of managed class
     */
    public function __construct(DocumentManager $documentManager, Collection $collection, ClassMetadata $classMetadata, Hydrator $hydrator, QueryCaster $queryCaster, UpdateQueryCreator $uqc = null) {
        $this->documentManager = $documentManager;
        $this->collection = $collection;
        $this->classMetadata = $classMetadata;
        $this->hydrator = $hydrator;

        $this->modelName = $classMetadata->getName();
        $this->objectCache = new ArrayCache();

        $this->queryCaster = $queryCaster;
        $this->updateQueryCreator = isset($uqc) ? $uqc : new UpdateQueryCreator();
    }

    public function clear(){
        $this->objectCache->flushAll();
    }

    /**
     * Count corresponding documents for filters
     * 
     * @param   array                   $filters            Object
     * @param   array                   $options            Options for the query
     * @return  int                                         Number of corresponding documents
     */
    public function count($filters = [], $options = []) {
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
    public function distinct($fieldName, $filters = [], $options = []) {
        $field = $fieldName;

        $propInfos = $this->classMetadata->getPropertyInfoForField($fieldName);
        if(!$propInfos){
            $propInfos = $this->classMetadata->getPropertyInfo($fieldName);
        }

        if(isset($propInfos)){
            $field = $propInfos->getField();

            if($propInfos->getMetadata()){
                $field = "metadata." . $field;
            }
        }

        $filters = $this->castQuery($filters);

        $this->log("debug", "Get distinct value of field '$field' in '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "options" => $options
            ]);

        $result = $this->collection->distinct($field, $filters, $options);
        return $result;
    }

    /**
     * Find document by ID
     * 
     * @param   mixed                   $id                 Id of the document
     * @param   array                   $projections        Projection of the query
     * @param   array                   $options            Options for the query
     * @return  object                                      Object corresponding to MongoDB Document (false if not found)
     */
    public function find($id, $projections = [], $options = []) {
        $options = $this->createOption($projections, null, $options);

        $this->log("debug", "Find object in collection '".$this->collection->getCollectionName()."' with id : '".(string) $id."'");

        $result = $this->collection->findOne(["_id" => $id], $options);

        return $this->createObject($result);
    }

    /**
     * Find all document of the collection
     * 
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findAll($projections = [], $sorts = [], $options = []) {
        $options = $this->createOption($projections, $sorts, $options);

        $this->log("debug", "Find all document in collection '".$this->collection->getCollectionName()."'");
        $result = $this->collection->find([], $options);

        $objects = [];

        foreach ($result as $datas) {
            if(null != ($object = $this->createObject($datas))){
                $objects[] = $object;
            }
        }

        return $objects;
    }

    /**
     * Find all document of the collection
     * 
     * @param   array                   $filters            Filters of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findBy($filters, $projections = [], $sorts = [], $options = []) {
        $options = $this->createOption($projections, $sorts, $options);

        $filters = $this->castQuery($filters);

        $this->log("debug", "Find documents in collection '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "options" => $options
            ]);

        $result = $this->collection->find($filters, $options);
        $objects = [];

        foreach ($result as $datas) {
            if(null != ($object = $this->createObject($datas))){
                $objects[] = $object;
            }
        }

        return $objects;
    }

    /**
     * Find all document of the collection
     * 
     * @param   array                   $filters            Filters of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  array                                       Array containing all the document of the collection
     */
    public function findOneBy($filters = [], $projections = [], $sorts = [], $options = []) {
        $options = $this->createOption($projections, $sorts, $options);

        $filters = $this->castQuery($filters);
        $this->log("debug", "Find one document in collection '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "options" => $options
            ]);

        $result = $this->collection->findOne($filters, $options);

        return $this->createObject($result);
    }

    /**
     * FindAndModifyOneBy document
     *
     * @param   array                   $filters            Filters of the query
     * @param   array                   $update             Update of the query
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sort options
     * @param   array                   $options            Options for the query
     * @return  object                                      Object correspoding to finding element 
     */
    public function findAndModifyOneBy($filters = [], $update = [], $projections = [], $sorts = [], $options = []) {

        $options = $this->createOption($projections, $sorts, $options);

        $filters = $this->castQuery($filters);
        $update = $this->castQuery($update);

        $this->log("debug", "Find and update one document in collection '".$this->collection->getCollectionName()."', see metadata for more details", [
            "filters" => $filters,
            "update" => $update,
            "options" => $options
            ]);

        $result = (array) $this->collection->findOneAndUpdate($filters, $update, $options);

        return $this->createObject($result);;
    }

    /**
     * Get tailable cursor for query
     * 
     * @param  array  $filters Filters of query
     * @param  array  $options Option (Tailable setted as default)
     * @return \MongoDB\Driver\TailableCursor          A tailable cursor
     */
    public function getTailableCursor($filters = [], $options = []) {
        $options['cursorType'] = \MongoDB\Operation\Find::TAILABLE_AWAIT;

        return $this->collection->find($this->castQuery($filters), $options);
    }

    public function insertOne($document, $options = []){
        $insertQuery = $this->hydrator->unhydrate($document);

        $result = $this->collection->insertOne($insertQuery, $options);

        if($result->isAcknowledged()){
            $insertQuery["_id"] = $result->getInsertedId();
            $this->hydrator->hydrate($document, $insertQuery);

            $this->cacheObject($document);

            return true;
        } else {
            return false;
        }
    }

    public function insertMany($documents, $options = []){
        $insertQuery = [];
        foreach ($documents as $document) {
            $insertQuery[] = $this->hydrator->unhydrate($document);
        }

        $result = $this->collection->insertMany($insertQuery, $options);

        if($result->isAcknowledged()){
            foreach ($result->getInsertedIds() as $key => $id) {
                $insertQuery[$key]["_id"] = $id;
                $this->hydrator->hydrate($documents[$key], $insertQuery[$key]);

                $this->cacheObject($documents[$key]);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * [updateOne description]
     * @param  [type] $document [description]
     * @param  array  $update   [description]
     * @param  array  $options  [description]
     * @return [type]           [description]
     *
     * @todo Delete refresh to make it from PHP (Don't make find query)
     */
    public function updateOne($document, $update = [], $options = []){
        if(is_object($document) && $document instanceof $this->modelName){
            $unhydratedObject = $this->hydrator->unhydrate($document);
            $id = $unhydratedObject["_id"];
            $filters = ["_id" => $id];
        } else if (is_object($document)){
            throw new MappingException('Document sended to update function must be of type "' . $this->modelName . '"');
        } else {
            $filters = $this->castQuery($document);
        }

        if(empty($update)){
            $update = $this->getUpdateQuery($document);
        } else {
            $update = $this->castQuery($update);
        }

        if(!empty($update)){
            $result = $this->collection->updateOne($filters, $update, $options);

            if($result->isAcknowledged()){
                if($document instanceof $this->modelName){
                    $this->documentManager->refresh($document);
                }
            } else {
                return false;
            }
        }

        $this->cacheObject($document);

        return true;
    }

    public function updateMany($filters, $update, $options = []){
        $result = $this->collection->updateMany($this->castQuery($filters), $update, $options);

        if($result->isAcknowledged()){
            return true;
        } else {
            return false;
        }
    }

    public function deleteOne($document, $options = []){
        $unhydratedObject = $this->hydrator->unhydrate($document);

        $id = $unhydratedObject["_id"];

        $result = $this->collection->deleteOne(["_id" => $id], $options);

        if($result->isAcknowledged()){
            return true;
        } else {
            return false;
        }
    }

    public function deleteMany($filter, $options = []){
        $filter = $this->castQuery($filter);
        $result = $this->collection->deleteMany($filter, $options);

        if($result->isAcknowledged()){
            return true;
        } else {
            return false;
        }
    }

    protected function log($level, $message, $metadata = []){
        if($this->documentManager->getDebug()){
            $this->documentManager->getLogger()->$level($message, $metadata);
        }
    }

    protected function createObject($data){
        $object = null;
        if($data != null){
            $model = $this->getModelName();
            $object = new $this->modelName();
            $this->hydrator->hydrate($object, $data);
            $this->cacheObject($object);
            $this->documentManager->addObject($object, DocumentManager::OBJ_MANAGED, $this);
            return $object;
        }
        return $object;
    }

    protected function createOption($projection, $sort, $otherOptions = []){
        $options = [];
        isset($projections) ? $options["projection"] = $this->castQuery($projections) : null ;
        isset($sort) ? $options["sort"] = $this->castQuery($sort) : null ;

        $options = array_merge($otherOptions, $options);

        return $options;
    }

    public function drop() {
        if($this->documentManager->getDebug())
            $this->documentManager->getLogger()->debug("Drop collection '".$this->collection->getCollectionName()."'");
        $result = $this->collection->drop();

        if ($result->ok) {
            return true;
        } else {
            return false;
        }
    }

    protected function castQuery($query) {
        $this->queryCaster->init($query);
        return $this->queryCaster->getCastedQuery();
    }

    public function cacheObject($object) {
        if (is_object($object)) {
            $this->objectCache->save(spl_object_hash($object), $this->hydrator->unhydrate($object));
        }
    }

    protected function uncacheObject($object) {
        return $this->objectCache->fetch(spl_object_hash($object));
    }

    protected function getUpdateQuery($document){
        $updateQuery = [];
        $old = $this->uncacheObject($document);
        $new = $this->hydrator->unhydrate($document);

        if(!$old){
            $query = ['$set' => $new]; 
        } else {
            $query = $this->updateQueryCreator->createUpdateQuery($old, $new);
        }

        unset($query['$set']["_id"]);

        return 
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
}
