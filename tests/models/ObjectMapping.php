<?php

namespace JPC\Test\MongoDB\ODM\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("object_mapping")
 */
class ObjectMapping {
    
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
}
