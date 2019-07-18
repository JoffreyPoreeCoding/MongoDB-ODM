<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Exception\MappingException;
use JPC\MongoDB\ODM\GridFS\Hydrator;
use JPC\MongoDB\ODM\Iterator\GridFSDocumentIterator;
use JPC\MongoDB\ODM\ObjectManager;
use JPC\MongoDB\ODM\Repository as BaseRepository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use MongoDB\Collection;
use MongoDB\GridFS\Bucket;
use JPC\MongoDB\ODM\GridFS\Tools\UpdateQueryCreator as GridFSUpdateQueryCreator;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
    public function __construct(
        DocumentManager $documentManager,
        Collection $collection,
        ClassMetadata $classMetadata,
        Hydrator $hydrator,
        QueryCaster $queryCaster = null,
        UpdateQueryCreator $uqc = null,
        CacheProvider $objectCache = null,
        CacheProvider $lastProjectionCache = null,
        EventDispatcher $eventDispatcher,
        Bucket $bucket = null
    ) {
        $uqc = $uqc ?? new GridFSUpdateQueryCreator();
        parent::__construct($documentManager, $collection, $classMetadata, $hydrator, $queryCaster, $uqc, $objectCache, $lastProjectionCache, $eventDispatcher);

        if ($this->modelName !== Document::class && !is_subclass_of($this->modelName, Document::class)) {
            throw new MappingException("Model must extends '" . Document::class . "'.");
        }

        $this->bucket = $bucket;
        if (!isset($this->bucket)) {
            $this->bucket = $documentManager->getDatabase()->selectGridFSBucket([
                "bucketName" => $this->classMetadata->getBucketName(),
            ]);
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
        $event = new BeforeQueryEvent($this->documentManager, $this, null);
        $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

        if (null !== ($object = parent::find($id, $projections, $options))) {
            $data = [];
            if ($this->getStreamProjection($projections)) {
                $data["stream"] = $this->bucket->openDownloadStream($object->getId());
            }
            $this->hydrator->hydrate($object, $data, true);
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
            $event = new BeforeQueryEvent($this->documentManager, $this, null);
            $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

            $objects = parent::findAll($projections, $sorts, $options);
            foreach ($objects as $object) {
                if ($this->getStreamProjection($projections)) {
                    $data["stream"] = $this->bucket->openDownloadStream($object->getId());
                }
                $this->hydrator->hydrate($object, $data, true);
            }
            return $objects;
        } else {
            if (!is_string($options['iterator'])) {
                $event = new BeforeQueryEvent($this->documentManager, $this, null);
                $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

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
            $event = new BeforeQueryEvent($this->documentManager, $this, null);
            $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

            $objects = parent::findBy($filters, $projections, $sorts, $options);
            foreach ($objects as $object) {
                $data = [];
                if ($this->getStreamProjection($projections)) {
                    $data["stream"] = $this->bucket->openDownloadStream($object->getId());
                }
                $this->hydrator->hydrate($object, $data, true);
            }
            return $objects;
        } else {
            if (!is_string($options['iterator'])) {
                $options['iterator'] = GridFSDocumentIterator::class;
            }

            $event = new BeforeQueryEvent($this->documentManager, $this, null);
            $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

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
        $event = new BeforeQueryEvent($this->documentManager, $this, null);
        $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

        $object = parent::findOneBy($filters, $projections, $sorts, $options);
        if (isset($object)) {
            $data = [];

            if ($this->getStreamProjection($projections)) {
                $data["stream"] = $this->bucket->openDownloadStream($object->getId());
                $this->hydrator->hydrate($object, $data, true);
            }
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
        $event = new BeforeQueryEvent($this->documentManager, $this, null);
        $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

        $object = parent::findAndModifyOneBy($filters, $update, $projections, $sorts, $options);

        if (isset($object)) {
            $data = [];
            if ($this->getStreamProjection($projections)) {
                $data["stream"] = $this->bucket->openDownloadStream($object->getId());
            }
            $this->hydrator->hydrate($object, $data, true);
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

        $event = new BeforeQueryEvent($this->documentManager, $this, null);
        $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);

        $id = $this->bucket->uploadFromStream($filename, $stream, $objectDatas);
        $data['_id'] = $id;

        $data["stream"] = $this->bucket->openDownloadStream($data['_id']);
        $this->hydrator->hydrate($document, $data, true);

        $this->documentManager->setObjectState($document, ObjectManager::OBJ_MANAGED);
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

        $event = new BeforeQueryEvent($this->documentManager, $this, null);
        $this->eventDispatcher->dispatch($event, BeforeQueryEvent::NAME);
        
        $this->bucket->delete($id);
        $this->documentManager->removeObject($document);
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
    public function getUpdateQuery($document)
    {
        $old = $this->uncacheObject($document);
        $new = $this->hydrator->unhydrate($document);
        unset($new["stream"]);
        $update = $this->updateQueryCreator->createUpdateQuery($old, $new);
        return $update;
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
