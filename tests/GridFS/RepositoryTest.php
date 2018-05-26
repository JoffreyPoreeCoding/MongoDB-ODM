<?php

namespace JPC\Test\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\GridFS\Hydrator;
use JPC\MongoDB\ODM\GridFS\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\EventManager;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use JPC\Test\MongoDB\ODM\GridFS\Model\GridFSObjectMapping;
use MongoDB\Collection;
use MongoDB\GridFS\Bucket;

class RepositoryTest extends TestCase
{

    private $documentManager;
    private $collection;
    private $classMetadata;
    private $hydrator;
    private $queryCaster;
    private $updateQueryCreator;
    private $bucket;

    private $repository;

    public function setUp()
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->documentManager->method('getDefaultOptions')->willReturn(['iterator' => true]);

        $this->collection = $this->createMock(Collection::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->hydrator = $this->createMock(Hydrator::class);
        $this->queryCaster = $this->createMock(QueryCaster::class);
        $this->updateQueryCreator = $this->createMock(UpdateQueryCreator::class);
        $this->bucket = $this->createMock(Bucket::class);

        $eventManagerMock = $this->createMock(EventManager::class);
        $this->classMetadata->method('getEventManager')->willReturn($eventManagerMock);
        $this->classMetadata->method("getName")->willReturn("JPC\Test\MongoDB\ODM\GridFS\Model\GridFSObjectMapping");
        $this->classMetadata->method("getBucketName")->willReturn("test");

        $this->repository = new Repository($this->documentManager, $this->collection, $this->classMetadata, $this->hydrator, $this->queryCaster, $this->updateQueryCreator, null, $this->bucket);
    }

    /**
     * @test
     */
    public function getBucket()
    {
        $this->assertEquals($this->bucket, $this->repository->getBucket());
    }

    /**
     * @test
     */
    public function find()
    {
        $this->collection->method("findOne")->willReturn(["filename" => "filename"]);
        $this->bucket->method("openDownloadStream")->willReturn("filecontent");
        $this->hydrator->method("hydrate")->will($this->returnCallback([$this, "fakeHydration"]));
        $object = $this->repository->find("test");

        $this->assertEquals("filecontent", $object->getStream());
        $this->assertEquals("filename", $object->getFilename());
    }

    /**
     * @test
     */
    public function findAll()
    {
        $this->collection->method("find")->willReturn([["_id" => 1, "filename" => "filename"], ["_id" => 2, "filename" => "filename"]]);
        $this->bucket->method("openDownloadStream")->willReturn("filecontent");
        $this->hydrator->method("hydrate")->will($this->returnCallback([$this, "fakeHydration"]));
        $objects = $this->repository->findAll();

        foreach ($objects as $i => $object) {
            $this->assertEquals("filecontent", $object->getStream());
            $this->assertEquals("filename", $object->getFilename());
        }
    }

    /**
     * @test
     */
    public function findBy()
    {
        $this->collection->method("find")->willReturn([["_id" => 1, "filename" => "filename"], ["_id" => 2, "filename" => "filename"]]);
        $this->bucket->method("openDownloadStream")->willReturn("filecontent");
        $this->hydrator->method("hydrate")->will($this->returnCallback([$this, "fakeHydration"]));
        $objects = $this->repository->findBy(["test" => "test"], [], [], ['cursor' => false]);

        foreach ($objects as $object) {
            $this->assertEquals("filecontent", $object->getStream());
            $this->assertEquals("filename", $object->getFilename());
        }
    }

    /**
     * @test
     */
    public function findOneBy()
    {
        $this->collection->method("findOne")->willReturn(["filename" => "filename"]);
        $this->bucket->method("openDownloadStream")->willReturn("filecontent");
        $this->hydrator->method("hydrate")->will($this->returnCallback([$this, "fakeHydration"]));
        $object = $this->repository->findOneBy(["test" => "test"]);

        $this->assertEquals("filecontent", $object->getStream());
        $this->assertEquals("filename", $object->getFilename());
    }

    /**
     * @test
     */
    public function findAndModifyOneBy()
    {
        $this->collection->method("findOneAndUpdate")->willReturn(["filename" => "filename"]);
        $this->bucket->method("openDownloadStream")->willReturn("filecontent");
        $this->hydrator->method("hydrate")->will($this->returnCallback([$this, "fakeHydration"]));
        $object = $this->repository->findAndModifyOneBy(["test" => "test"]);

        $this->assertEquals("filecontent", $object->getStream());
        $this->assertEquals("filename", $object->getFilename());
    }

    /**
     * @test
     */
    public function drop()
    {
        $this->bucket->expects($this->once())->method("drop");
        $this->repository->drop();
    }

    /**
     * @test
     */
    public function insertOne()
    {
        $this->hydrator->method("unhydrate")->willReturn(["filename" => "filename", "_id" => "id", "stream" => "stream"]);

        $this->bucket->expects($this->once())->method("uploadFromStream")->with("filename", "stream", ["_id" => "id"]);

        $document = new GridFSObjectMapping();

        $this->repository->insertOne($document);
    }

    /**
     * @test
     */
    public function insertMany()
    {
        $this->hydrator->method("unhydrate")->willReturn(["filename" => "filename", "_id" => "id", "stream" => "stream"]);

        $this->bucket->expects($this->exactly(2))->method("uploadFromStream")->with("filename", "stream", ["_id" => "id"]);

        $document1 = new GridFSObjectMapping();
        $document2 = new GridFSObjectMapping();

        $this->repository->insertMany([$document1, $document2]);
    }

    /**
     * @test
     */
    public function deleteOne()
    {
        $this->hydrator->method("unhydrate")->willReturn(["_id" => "id"]);
        $this->bucket->expects($this->once())->method("delete")->with("id");

        $this->repository->deleteOne(new GridFSObjectMapping());
    }

    /**
     * @test
     */
    public function deleteMany()
    {
        $this->expectException(\JPC\MongoDB\ODM\GridFS\Exception\DeleteManyException::class);
        $this->repository->deleteMany([]);
    }

    public function fakeHydration($object, $data)
    {
        $object
            ->setFilename("filename");

        if (isset($data["stream"])) {
            $object->setStream("filecontent");
        }
    }
}
