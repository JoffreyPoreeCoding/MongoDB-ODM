<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Factory\ClassMetadataFactory;
use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo;
use JPC\MongoDB\ODM\Tools\QueryCaster;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class QueryCasterTest extends TestCase {
    
    /**
     * @var QueryCaster 
     */
    private $queryCaster;
    
    public function test_castQuery(){
        $metadata = $this->createMetadataMock();
        
        $query = [
            "simple" => "value",
            "embedded" => [
                "value" => "val"
            ],
            "dotted.value" => "val_dotted",
            "special" => ['$gt' => 10]
        ];
                
        $this->queryCaster = new QueryCaster($metadata, new ClassMetadataFactory());
        $this->queryCaster->init($query, $this->createMetadataMock());
        
        $expected = [
            "simple_field" => "value",
            "embedded_field" => [
                "value" => "val"
            ],
            "dotted_field.value" => "val_dotted",
            "special_field" => ['$gt' => 10]
        ];
       
        $this->assertEquals($expected, $this->queryCaster->getCastedQuery());
    }
    
    private function createMetadataMock(){
        $metadata = $this->createMock(ClassMetadata::class);
        $map = [
            ["simple", $this->createPropertyInfoMock("simple_field")],
            ["embedded", $this->createPropertyInfoMock("embedded_field")],
            ["dotted", $this->createPropertyInfoMock("dotted_field")],
            ["special", $this->createPropertyInfoMock("special_field")]
        ];
        $metadata->method("getPropertyInfo")
                ->will($this->returnValueMap($map));
        return $metadata;
    }
    
    private function createPropertyInfoMock($fieldValue){
        $propInfo = $this->createMock(PropertyInfo::class);
        $propInfo->method("getField")->willReturn($fieldValue);
        return $propInfo;
    }
}
