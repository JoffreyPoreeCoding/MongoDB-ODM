<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Tools\ClassMetadata\ClassMetadata;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class ClassMetadataTest extends TestCase
{

    /**
     * @var ClassMetadata
     */
    private $classMetadata;

    public function setUp()
    {
        $this->classMetadata = new ClassMetadata(\JPC\Test\MongoDB\ODM\Tools\ObjectMapping::class);
    }

    public function testGetName()
    {
        $this->assertEquals("JPC\Test\MongoDB\ODM\Tools\ObjectMapping", $this->classMetadata->getName());
    }

    public function testGetColection()
    {
        $this->assertEquals("object_mapping", $this->classMetadata->getCollection());
    }

    public function testGetPropertiesInfos()
    {
        $this->assertContainsOnlyInstancesOf("JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo", $this->classMetadata->getPropertiesInfos());
    }

    public function testGetPropertyForField()
    {
        $this->assertInstanceOf("ReflectionProperty", $this->classMetadata->getPropertyForField("simple_field"));
        $this->assertFalse($this->classMetadata->getPropertyForField("inexisting"));
    }

    public function testGetPropertyInfoForField()
    {
        $this->assertInstanceOf("JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo", $this->classMetadata->getPropertyInfo("simpleField"));
        $this->assertFalse($this->classMetadata->getPropertyInfo("inexisting"));
    }

    public function testGetPropertyInfo()
    {
        $this->assertInstanceOf("JPC\MongoDB\ODM\Tools\ClassMetadata\Info\PropertyInfo", $this->classMetadata->getPropertyInfoForField("simple_field"));
        $this->assertFalse($this->classMetadata->getPropertyInfoForField("inexisting"));
    }

    public function testGetRepositoryClass()
    {
        $this->assertEquals("JPC\MongoDB\ODM\Repository", $this->classMetadata->getRepositoryClass());
    }

    public function testGetCollectionOptions()
    {
        $this->assertEmpty($this->classMetadata->getCollectionOptions());
    }

    public function testGetCollectionCreationOptions()
    {
        $this->assertEmpty($this->classMetadata->getCollectionCreationOptions());
    }

    public function testGetEventManager()
    {
        $this->assertEquals(["model.pre_persist" => ["event"]], $this->classMetadata->getEvents());
    }
}

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\Annotations\Event;

/**
 * @ODM\Document("object_mapping")
 * @Event\HasLifecycleCallbacks
 */
class ObjectMapping
{

    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field("simple_field")
     */
    private $simpleField;

    /**
     * @ODM\Field("embedded_field")
     * @ODM\EmbeddedDocument("ObjectMapping")
     */
    private $embeddedField;

    /**
     * @ODM\Field("multi_embedded_field")
     * @ODM\MultiEmbeddedDocument("ObjectMapping")
     */
    private $multiEmbeddedField;

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the value of id.
     *
     * @param mixed $id the id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the value of simple.
     *
     * @return mixed
     */
    public function getSimpleField()
    {
        return $this->simpleField;
    }

    /**
     * Sets the value of simpleField.
     *
     * @param mixed $simpleField the simpleField
     *
     * @return self
     */
    public function setSimpleField($simpleField)
    {
        $this->simpleField = $simpleField;

        return $this;
    }

    /**
     * Gets the value of embeddedField.
     *
     * @return mixed
     */
    public function getEmbeddedField()
    {
        return $this->embeddedField;
    }

    /**
     * Sets the value of embeddedField.
     *
     * @param mixed $embeddedField the embedded field
     *
     * @return self
     */
    public function setEmbeddedField($embeddedField)
    {
        $this->embeddedField = $embeddedField;

        return $this;
    }

    /**
     * Gets the value of multiEmbeddedField.
     *
     * @return mixed
     */
    public function getMultiEmbeddedField()
    {
        return $this->multiEmbeddedField;
    }

    /**
     * Sets the value of multiEmbeddedField.
     *
     * @param mixed $multiEmbeddedField the multi embedded field
     *
     * @return self
     */
    public function setMultiEmbeddedField($multiEmbeddedField)
    {
        $this->multiEmbeddedField = $multiEmbeddedField;

        return $this;
    }

    /**
     * @Event\PrePersist
     */
    public function event()
    {
        echo "HEYYY";
    }
}
