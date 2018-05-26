<?php

namespace JPC\Test\MongoDB\ODM;

use JPC\MongoDB\ODM\DocumentManager;
use JPC\MongoDB\ODM\Hydrator;
use JPC\MongoDB\ODM\Repository;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo;
use JPC\MongoDB\ODM\Tools\EventManager;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use JPC\Test\MongoDB\ODM\Framework\TestCase;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\InsertManyResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

class RepositoryTest extends TestCase
{

    private $documentManagerMock;

    private $collectionMock;

    private $classMetadataMock;

    private $hydratorMock;

    private $queryCasterMock;

    private $updateQueryCreatorMock;

    private $repositoryMockBuilder;

    public function setUp()
    {
        parent::setup();
        $this->documentManagerMock = $this->createMock(DocumentManager::class);
        $this->documentManagerMock->method('getDefaultOptions')->willReturn(['iterator' => true]);

        $this->collectionMock = $this->createMock(Collection::class);
        $this->classMetadataMock = $this->createMock(ClassMetadata::class);
        $this->hydratorMock = $this->createMock(Hydrator::class);
        $this->queryCasterMock = $this->createMock(QueryCaster::class);
        $this->updateQueryCreatorMock = $this->createMock(UpdateQueryCreator::class);

        $eventManagerMock = $this->createMock(EventManager::class);
        $this->classMetadataMock->method('getEventManager')->willReturn($eventManagerMock);

        $this->repositoryMockBuilder = $this->getMockBuilder(Repository::class)
            ->setConstructorArgs([$this->documentManagerMock, $this->collectionMock, $this->classMetadataMock, $this->hydratorMock, $this->queryCasterMock, $this->updateQueryCreatorMock])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes();
    }

    public function testCount()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "v"]);

        $this->collectionMock->expects($this->once())->method("count")->with(["f" => "v"], ["option" => "value"])->willReturn(10);

        $count = $repository->count(["filter" => "value"], ["option" => "value"]);
        $this->assertEquals(10, $count);
    }

    public function testDistinctNoField()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["castQuery", "log"])
            ->getMock();

        $propInfo = $this->createMock(PropertyInfo::class);
        $propInfo->method("getField")->willReturn("f");

        $this->classMetadataMock->expects($this->once())->method("getPropertyInfoForField")->with("inexisting")->willReturn(null);
        $this->classMetadataMock->expects($this->once())->method("getPropertyInfo")->with("inexisting")->willReturn(null);

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $this->collectionMock->expects($this->once())->method("distinct")->with("inexisting", ["f" => "value"], ["option" => "value"]);

        $repository->distinct("inexisting", ["filter" => "value"], ["option" => "value"]);
    }

    public function testDistinctProperty()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["castQuery", "log"])
            ->getMock();

        $propInfo = $this->createMock(PropertyInfo::class);
        $propInfo->method("getField")->willReturn("f");

        $this->classMetadataMock
            ->expects($this->once())
            ->method("getPropertyInfoForField")
            ->with("property")
            ->willReturn(null);
        $this->classMetadataMock
            ->expects($this->once())
            ->method("getPropertyInfo")
            ->with("property")
            ->willReturn($propInfo);

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $this->collectionMock->expects($this->once())->method("distinct")->with("f", ["f" => "value"], ["option" => "value"]);

        $repository->distinct("property", ["filter" => "value"], ["option" => "value"]);
    }

    public function testDistinctField()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["castQuery", "log"])
            ->getMock();

        $propInfo = $this->createMock(PropertyInfo::class);
        $propInfo->method("getField")->willReturn("f");

        $this->classMetadataMock
            ->expects($this->once())
            ->method("getPropertyInfoForField")
            ->with("field")
            ->willReturn($propInfo);
        $this->classMetadataMock
            ->expects($this
                    ->exactly(0))
            ->method("getPropertyInfo");

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $this->collectionMock->expects($this->once())->method("distinct")->with("f", ["f" => "value"], ["option" => "value"]);

        $repository->distinct("field", ["filter" => "value"], ["option" => "value"]);
    }

    public function testFindNoResult()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log"])->getMock();

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], null, ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("findOne")->with(["_id" => "id"], ["option1" => "value1", "option2" => "value2"])->willReturn(null);

        $result = $repository->find("id", ["projection" => "value"], ["option" => "value"]);

        $this->assertNull($result);
    }

    public function testFind()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "createObject"])->getMock();

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], null, ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("findOne")->with(["_id" => "id"], ["option1" => "value1", "option2" => "value2"])->willReturn(["data" => "value"]);

        $repository->expects($this->once())->method("createObject")->with(["data" => "value"])->willReturn(true);

        $result = $repository->find("id", ["projection" => "value"], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testFindAllNoResult()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log"])->getMock();

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("find")->with([], ["option1" => "value1", "option2" => "value2"])->willReturn([]);

        $result = $repository->findAll(["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertEquals([], $result);
    }

    public function testFindAll()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "createObject"])->getMock();

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("find")->with([], ["option1" => "value1", "option2" => "value2"])->willReturn([1, 2, 3]);

        $repository->expects($this->exactly(3))->method("createObject")->with($this->logicalOr(
            1,
            2,
            3
        ))->will($this->onConsecutiveCalls(4, 5, 6));

        $result = $repository->findAll(["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertEquals([4, 5, 6], $result);
    }

    public function testFindByNoResult()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("find")->with(["f" => "value"], ["option1" => "value1", "option2" => "value2"])->willReturn([]);

        $result = $repository->findBy(["filter" => "value"], ["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertEquals([], $result);
    }

    public function testFindBy()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "castQuery", "createObject"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("find")->with(["f" => "value"], ["option1" => "value1", "option2" => "value2"])->willReturn([1, 2, 3]);

        $repository->expects($this->exactly(3))->method("createObject")->with($this->logicalOr(
            1,
            2,
            3
        ))->will($this->onConsecutiveCalls(4, 5, 6));

        $result = $repository->findBy(["filter" => "value"], ["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertEquals([4, 5, 6], $result);
    }

    public function testFindOneByNoResult()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("findOne")->with(["f" => "value"], ["option1" => "value1", "option2" => "value2"])->willReturn(null);

        $result = $repository->findOneBy(["filter" => "value"], ["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertNull($result);
    }

    public function testFindOneBy()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "createObject", "castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("findOne")->with(["f" => "value"], ["option1" => "value1", "option2" => "value2"])->willReturn(["data" => "value"]);

        $repository->expects($this->once())->method("createObject")->with(["data" => "value"])->willReturn(true);

        $result = $repository->findOneBy(["filter" => "value"], ["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testFindAndModifyOneByNoResult()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "castQuery"])->getMock();

        $repository->expects($this->exactly(2))->method("castQuery")->with($this->logicalOr(
            ["filter" => "value"],
            ["update" => "value"]
        ))->will($this->onConsecutiveCalls(["f" => "value"], ["u" => "value"]));

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("findOneAndUpdate")->with(["f" => "value"], ["u" => "value"], ["option1" => "value1", "option2" => "value2"])->willReturn(null);

        $result = $repository->findAndModifyOneBy(["filter" => "value"], ["update" => "value"], ["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertNull($result);
    }

    public function testFindAndModifyOneBy()
    {
        $repository = $this->repositoryMockBuilder
            ->setMethods(["createOption", "log", "castQuery", "createObject"])->getMock();

        $repository->expects($this->exactly(2))->method("castQuery")->with($this->logicalOr(
            ["filter" => "value"],
            ["update" => "value"]
        ))->will($this->onConsecutiveCalls(["f" => "value"], ["u" => "value"]));

        $repository->method("log")->willReturn(null);

        $repository->expects($this->once())->method("createOption")->with(["projection" => "value"], ["sort" => "value"], ["option" => "value"])->willReturn(["option1" => "value1", "option2" => "value2"]);

        $this->collectionMock->expects($this->once())->method("findOneAndUpdate")->with(["f" => "value"], ["u" => "value"], ["option1" => "value1", "option2" => "value2"])->willReturn(["data" => "value"]);

        $repository->expects($this->once())->method("createObject")->with(["data" => "value"])->willReturn(true);

        $result = $repository->findAndModifyOneBy(["filter" => "value"], ["update" => "value"], ["projection" => "value"], ["sort" => "value"], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testGetTailableCursor()
    {
        $repository = $this->repositoryMockBuilder->setMethods(["castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "value"]);

        $this->collectionMock->expects($this->once())->method("find")->with(["f" => "value"], ['cursorType' => \MongoDB\Operation\Find::TAILABLE_AWAIT, "option" => "value"])->willReturn(true);

        $result = $repository->getTailableCursor(["filter" => "value"], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testInsertOne()
    {
        $repository = $this->repositoryMockBuilder->setMethods(["cacheObject"])->getMock();

        $document = new \stdClass();

        $this->hydratorMock->expects($this->once())->method("unhydrate")->with($document)->willReturn(["field" => "value"]);
        $this->hydratorMock->expects($this->once())->method("hydrate")->with($document, ["_id" => 1, "field" => "value"])->will($this->returnCallback(function ($doc, $data) use ($document) {
            $doc->id = $data["_id"];
            $doc->field = $data["field"];
        }));

        $insertOneResult = $this->createMock(InsertOneResult::class);
        $insertOneResult->method("isAcknowledged")->willReturn(true);
        $insertOneResult->method("getInsertedId")->willReturn(1);

        $this->collectionMock->expects($this->once())->method("insertOne")->with(["field" => "value"], ["option" => "value"])->willReturn($insertOneResult);

        $repository->insertOne($document, ["option" => "value"]);

        $this->assertEquals(1, $document->id);
        $this->assertEquals("value", $document->field);
    }

    /**
     * @TODO("VERIFIER SI DOCUMENT MANAGER addObject et setOBjectState are called")
     */
    public function testInsertMany()
    {
        $repository = $this->repositoryMockBuilder->setMethods(["cacheObject"])->getMock();

        $documentOne = new \stdClass();
        $documentTwo = new \stdClass();
        $documentThree = new \stdClass();

        $documents = [$documentOne, $documentTwo, $documentThree];

        $this->hydratorMock->expects($this->exactly(3))->method("unhydrate")->willReturn(["field" => "value"]);
        $this->hydratorMock->expects($this->exactly(3))->method("hydrate")->will($this->returnCallback(function ($document, $data) {
            $document->id = $data["_id"];
            $document->field = $data["field"];
        }));

        $insertManyResult = $this->createMock(InsertManyResult::class);
        $insertManyResult->method("isAcknowledged")->willReturn(true);
        $insertManyResult->method("getInsertedIds")->willReturn([1, 2, 3]);

        $this->collectionMock->expects($this->once())->method("insertMany")->with([["field" => "value"], ["field" => "value"], ["field" => "value"]], ["option" => "value"])->willReturn($insertManyResult);

        $repository->insertMany($documents, ["option" => "value"]);

        $this->assertEquals(1, $documentOne->id);
        $this->assertEquals("value", $documentOne->field);

        $this->assertEquals(2, $documentTwo->id);
        $this->assertEquals("value", $documentTwo->field);

        $this->assertEquals(3, $documentThree->id);
        $this->assertEquals("value", $documentThree->field);
    }

    public function testUpdateOne()
    {
        $this->classMetadataMock->method("getName")->willReturn("stdClass");
        $repository = $this->repositoryMockBuilder->setMethods(["castQuery", "getUpdateQuery"])->getMock();

        $this->hydratorMock->expects($this->exactly(1))->method("unhydrate")->willReturn(["_id" => 1]);

        $repository->expects($this->exactly(2))->method("getUpdateQuery")->willReturn(["update" => "value"]);
        $repository->expects($this->any())->method("castQuery");

        $result = $this->createMock(UpdateResult::class);
        $result->method("isAcknowledged")->willReturn(true);

        $this->collectionMock->expects($this->once())->method("updateOne")->with(["_id" => 1], ["update" => "value"], ["option" => "value"])->willReturn($result);

        $document = new \stdClass();

        $result = $repository->updateOne($document, [], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testUpdateOneBadObject()
    {
        $this->classMetadataMock->method("getName")->willReturn("anotherObject");
        $repository = $this->repositoryMockBuilder->setMethods(null)->getMock();

        $this->classMetadataMock->method("getName")->willReturn("anotherObject");
        $document = new \stdClass();

        $this->expectException(\JPC\MongoDB\ODM\Exception\MappingException::class);
        $repository->updateOne($document, [], ["option" => "value"]);
    }

    public function testUpdateOneEmptyUpdateQuery()
    {
        $this->classMetadataMock->method("getName")->willReturn("stdClass");
        $repository = $this->repositoryMockBuilder->setMethods(["castQuery", "getUpdateQuery"])->getMock();

        $this->hydratorMock->expects($this->exactly(2))->method("unhydrate")->willReturn(["_id" => 1]);

        $repository->expects($this->once())->method("getUpdateQuery")->willReturn([]);
        $repository->expects($this->any())->method("castQuery");

        $result = $this->createMock(UpdateResult::class);
        $result->method("isAcknowledged")->willReturn(true);

        $this->collectionMock->expects($this->any())->method("updateOne");

        $document = new \stdClass();
        $result = $repository->updateOne($document, [], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testUpdateOneWithFilter()
    {
        $this->classMetadataMock->method("getName")->willReturn("stdClass");
        $repository = $this->repositoryMockBuilder->setMethods(["castQuery", "getUpdateQuery"])->getMock();

        $this->hydratorMock->expects($this->any())->method("unhydrate");

        $repository->expects($this->any())->method("getUpdateQuery");
        $repository->expects($this->exactly(2))->method("castQuery")->will($this->onConsecutiveCalls(["query" => "value"], ["update" => "value"]));

        $result = $this->createMock(UpdateResult::class);
        $result->method("isAcknowledged")->willReturn(true);

        $this->collectionMock->expects($this->once())->method("updateOne")->with(["query" => "value"], ["update" => "value"], ["option" => "value"])->willReturn($result);

        $result = $repository->updateOne(["query" => "value"], ["update" => "value"], ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testUpdateMany()
    {
        $repository = $this->repositoryMockBuilder->setMethods(["castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->willReturn(["filter" => "value"]);

        $result = $this->createMock(UpdateResult::class);
        $result->method("isAcknowledged")->willReturn(true);

        $this->collectionMock->expects($this->once())->method("updateMany")->with(["filter" => "value"], ["update" => "value"], ["option" => "value"])->willReturn($result);

        $this->assertTrue($repository->updateMany(["filter" => "value"], ["update" => "value"], ["option" => "value"]));
    }

    public function testDeleteOne()
    {
        $repository = $this->repositoryMockBuilder->setMethods(null)->getMock();

        $this->hydratorMock->method("unhydrate")->willReturn(["_id" => 1]);

        $deleteResult = $this->createMock(DeleteResult::class);
        $deleteResult->method("isAcknowledged")->willReturn(true);

        $this->collectionMock->expects($this->once())->method("deleteOne")->with(["_id" => 1], ["option" => "value"])->willReturn($deleteResult);

        $document = new \stdClass();

        $result = $repository->deleteOne($document, ["option" => "value"]);

        $this->assertTrue($result);
    }

    public function testDeleteMany()
    {
        $repository = $this->repositoryMockBuilder->setMethods(["castQuery"])->getMock();

        $repository->expects($this->once())->method("castQuery")->with(["filter" => "value"])->willReturn(["f" => "v"]);

        $deleteResult = $this->createMock(DeleteResult::class);
        $deleteResult->method("isAcknowledged")->willReturn(true);
        $deleteResult->method("getDeletedCount")->willReturn(3);

        $this->collectionMock->expects($this->once())->method("deleteMany")->with(["f" => "v"], ["option" => "value"])->willReturn($deleteResult);

        $document = new \stdClass();

        $result = $repository->deleteMany(["filter" => "value"], ["option" => "value"]);

        $this->assertEquals(3, $result);
    }

    public function testDetUpdateQuery()
    {
        $repository = $this->repositoryMockBuilder->setMethods(["uncacheObject"])->getMock();

        $repository->method("uncacheObject")->willReturn("old");
        $this->hydratorMock->method("unhydrate")->willReturn("new");

        $this->updateQueryCreatorMock->expects($this->once())->method("createUpdateQuery")->with("old", "new")->willReturn("updateQuery");

        $method = new \ReflectionMethod(get_class($repository), "getUpdateQuery");
        $method->setAccessible(true);
        $query = $method->invokeArgs($repository, [new \stdClass()]);

        $this->assertEquals("updateQuery", $query);
    }
}
