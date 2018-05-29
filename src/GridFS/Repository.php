<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\GridFS\Hydrator;
use JPC\MongoDB\ODM\Iterator\GridFSDocumentIterator;
use JPC\MongoDB\ODM\Repository as BaseRepository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use MongoDB\Collection;
use MongoDB\GridFS\Bucket;

/**
 * Repository to make action on gridfs bucket
 */
class Repository extends BaseRepository
{

    /**
     * GridFS Bucket
     * @var \MongoDB\GridFS\Bucket
     */
    protected $bucket;

    /**
     * Create a Grid FS repository
     *
     * @param DocumentManager       $documentManager    Linked document manager
     * @param Collection            $collection         Collection where action will be performer
     * @param ClassMetadata         $classMetadata      Class metadata of current model
     * @param Hydrator              $hydrator           Hydrator
     * @param QueryCaster           $queryCaster        Query caster
     * @param UpdateQueryCreator    $uqc                Update query creator
     * @param CacheProvider         $objectCache        Cache to store object states
     * @param Bucket                $bucket             GridFS Bucket
     */
    public function __construct(DocumentManager $documentManager, Collection $collection, ClassMetadata $classMetadata, Hydrator $hydrator, QueryCaster $queryCaster = null, UpdateQueryCreator $uqc = null, CacheProvider $objectCache = null, Bucket $bucket = null)
    {
        parent::__construct($documentManager, $collection, $classMetadata, $hydrator, $queryCaster, $uqc, $objectCache);

        if ($this->modelName !== Document::class && !is_subclass_of($this->modelName, Document::class)) {
            throw new MappingException("Model must extends '" . Document::class . "'.");
        }

        $this->bucket = $bucket;
        if (!isset($this->bucket)) {
            $this->bucket = $documentManager->getDatabase()->selectGridFSBucket(["bucketName" => $this->classMetadata->getBucketName()]);
        }
    }

    /**
     * Get the GridFS Bucket
     *
     * @return Bucket
     */
    public function getBucket()
    {
        return $this->bucket;
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
    public function find($id, $projections = array(), $options = array())
    {
        if (null !== ($object = parent::find($id, $projections, $options))) {
            if ($this->getStreamProjection($projections)) {
                $data["stream"] = $this->bucket->openDownloadStream($object->getId());
            }
            $this->hydrator->hydrate($object, $data);
            return $object;
        }
    }

    /**
     * Get all documents of collection
     *
     * Options :
     *  *   readOnly : boolean - When false, flush will not update object
     *  *   iterator : boolean|string - Return DocumentIterator if true (or specified class if is string)
     * @see MongoDB\Operation\Find::__construct for more option
     *
     * @param   array                   $projections        Projection of the query
     * @param   array                   $sorts              Sorts specification
     * @param   array                   $options            Options for the query
     * @param array $options
     * @return void
     */
    public function findAll($projections = array(), $sorts = array(), $options = array())
    {
        $options = $this->createOption($projections, $sorts, $options);
        if (!isset($options['iterator']) || $options['iterator'] === false) {
            $objects = parent::findAll($projections, $sorts, $options);
            foreach ($objects as $object) {
                if ($this->getStreamProjection($projections)) {
                    $data["stream"] = $this->bucket->openDownloadStream($object->getId());
                }
                $this->hydrator->hydrate($object, $data);
            }
            return $objects;
        } else {
            if (!is_string($options['iterator'])) {
                $options['iterator'] = GridFSDocumentIterator::class;
            }
            return parent::findAll($projections, $sorts, $options);
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
    public function findBy($filters, $projections = array(), $sorts = array(), $options = array())
    {
        $options = $this->createOption($projections, $sorts, $options);
        if (!isset($options['iterator']) || $options['iterator'] === false) {
            $objects = parent::findBy($filters, $projections, $sorts, $options);
            foreach ($objects as $object) {
                if ($this->getStreamProjection($projections)) {
                    $data["stream"] = $this->bucket->openDownloadStream($object->getId());
                }
                $this->hydrator->hydrate($object, $data);
            }
            return $objects;
        } else {
            if (!is_string($options['iterator'])) {
                $options['iterator'] = GridFSDocumentIterator::class;
            }
            return parent::findBy($filters, $projections, $sorts, $options);
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
    public function findOneBy($filters = array(), $projections = array(), $sorts = array(), $options = array())
    {
        $object = parent::findOneBy($filters, $projections, $sorts, $options);

        if (isset($object)) {
            if ($this->getStreamProjection($projections)) {
                $data["stream"] = $this->bucket->openDownloadStream($object->getId());
            }
            $this->hydrator->hydrate($object, $data);
            return $object;
        }
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
        $object = parent::findAndModifyOneBy($filters, $update, $projections, $sorts, $options);

        if (isset($object)) {
            if ($this->getStreamProjection($projections)) {
                $data["stream"] = $this->bucket->openDownloadStream($object->getId());
            }
            $this->hydrator->hydrate($object, $data);
            return $object;
        }
    }

    /**
     * Drop the bucket from database
     *
     * @return void
     */
    public function drop()
    {
        $this->bucket->drop();
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
            $unhyd = $this->hydrator->unhydrate($object);
            unset($unhyd["stream"]);
            $this->objectCache->save(spl_object_hash($object), $unhyd);
        }
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

    /**
     * Insert a GridFS document
     *
     * @param   object  $document Document to insert
     * @param   array   $options  Useless, just for Repository compatibility
     * @return  boolean
     */
    public function insertOne($document, $options = [])
    {
        $objectDatas = $this->hydrator->unhydrate($document);

        $stream = $objectDatas["stream"];
        unset($objectDatas["stream"]);

        if (!isset($objectDatas["filename"])) {
            $filename = stream_get_meta_data($stream)["uri"];
        } else {
            $filename = $objectDatas["filename"];
        }

        unset($objectDatas["filename"]);

        $this->bucket->uploadFromStream($filename, $stream, $objectDatas);

        $data["stream"] = $this->bucket->openDownloadStream($document->getId());
        $this->hydrator->hydrate($document, $data);

        $this->cacheObject($document);

        return true;
    }

    /**
     * Insert multiple GridFS documents
     *
     * @param   array   $documents  Documents to insert
     * @param   array   $options    Useless, just for Repository compatibility
     * @return  boolean
     */
    public function insertMany($documents, $options = [])
    {
        foreach ($documents as $document) {
            if (!$this->insertOne($document)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete a document form gr
     *
     * @param   object|array    $document   Document or query to delete
     * @param   array           $options    Useless, just for Repository compatibility
     * @return void
     */
    public function deleteOne($document, $options = [])
    {
        $unhydratedObject = $this->hydrator->unhydrate($document);

        $id = $unhydratedObject["_id"];

        $this->bucket->delete($id);
    }

    /**
     * Delete a document form gr
     *
     * @param   array   $filters    Query that match documents to delete
     * @param   array   $options    Useless, just for Repository compatibility
     * @return void
     */
    public function deleteMany($filter, $options = [])
    {
        throw new \JPC\MongoDB\ODM\GridFS\Exception\DeleteManyException();
    }

    /**
     * Create the update query from object diff
     *
     * @param   object  $document   The document that the update query will match
     * @return  array
     */
    protected function getUpdateQuery($document)
    {
        $updateQuery = [];
        $old = $this->uncacheObject($document);
        $new = $this->hydrator->unhydrate($document);
        unset($new["stream"]);

        return $this->updateQueryCreator->createUpdateQuery($old, $new);
    }

    /**
     * Get the stream projection
     *
     * @param   array   $projections    Projection of query
     * @return  boolean
     */
    private function getStreamProjection($projections)
    {
        if (isset($projections['stream'])) {
            return $projections['stream'];
        } elseif (empty($projections)) {
            return true;
        } else {
            if (isset($projections['_id'])) {
                unset($projections['_id']);
            }
            return reset($projections) ? false : true;
        }
    }
}
