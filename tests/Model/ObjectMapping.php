<?php

namespace JPC\Test\MongoDB\ODM\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("object_mapping")
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
     * @ODM\Field("refers_one_field")
     * @ODM\RefersOne("ObjectMapping")
     */
    private $refersOneField;

    /**
     * @ODM\Field("refers_many_field")
     * @ODM\RefersMany("ObjectMapping")
     */
    private $refersManyField;

    /**
     * @ODM\Field("simple_discriminated_field")
     * @ODM\EmbeddedDocument("Discriminated1")
     * @ODM\DiscriminatorField("type")
     * @ODM\DiscriminatorMap({1="Discriminated1", 2="Discriminated2"})
     */
    private $simpleDiscriminatedField;

    /**
     * @ODM\Field("multi_discriminated_field")
     * @ODM\MultiEmbeddedDocument("Discriminated1")
     * @ODM\DiscriminatorField("type")
     * @ODM\DiscriminatorMap({1="Discriminated1", 2="Discriminated2"})
     */
    private $multiDiscriminatedField;

    /**
     * @ODM\Field("method_discriminated_field")
     * @ODM\EmbeddedDocument("Discriminated1")
     * @ODM\DiscriminatorMethod("discrimateMethod")
     */
    private $methodDiscriminatedField;

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
     * Gets the value of refersOneField.
     *
     * @return mixed
     */
    public function getRefersOneField()
    {
        return $this->refersOneField;
    }

    /**
     * Sets the value of refersOneField.
     *
     * @param mixed $refersOneField the refers one field
     *
     * @return self
     */
    public function setRefersOneField($refersOneField)
    {
        $this->refersOneField = $refersOneField;

        return $this;
    }

    /**
     * Gets the value of refersManyField.
     *
     * @return mixed
     */
    public function getRefersManyField()
    {
        return $this->refersManyField;
    }

    /**
     * Sets the value of refersManyField.
     *
     * @param mixed $refersManyField the refers many field
     *
     * @return self
     */
    public function setRefersManyField($refersManyField)
    {
        $this->refersManyField = $refersManyField;

        return $this;
    }

    /**
     * Get the value of simpleDiscriminatedField
     */
    public function getSimpleDiscriminatedField()
    {
        return $this->simpleDiscriminatedField;
    }

    /**
     * Set the value of simpleDiscriminatedField
     *
     * @return  self
     */
    public function setSimpleDiscriminatedField($simpleDiscriminatedField)
    {
        $this->simpleDiscriminatedField = $simpleDiscriminatedField;

        return $this;
    }

    /**
     * Get the value of multiDiscriminatedField
     */
    public function getMultiDiscriminatedField()
    {
        return $this->multiDiscriminatedField;
    }

    /**
     * Set the value of multiDiscriminatedField
     *
     * @return  self
     */
    public function setMultiDiscriminatedField($multiDiscriminatedField)
    {
        $this->multiDiscriminatedField = $multiDiscriminatedField;

        return $this;
    }

    /**
     * Get the value of methodDiscriminatedField
     */
    public function getMethodDiscriminatedField()
    {
        return $this->methodDiscriminatedField;
    }

    /**
     * Set the value of methodDiscriminatedField
     *
     * @return  self
     */
    public function setMethodDiscriminatedField($methodDiscriminatedField)
    {
        $this->methodDiscriminatedField = $methodDiscriminatedField;

        return $this;
    }

    public function discrimateMethod($data)
    {
        return $data['type'] % 2 == 1 ? 'Discriminated1' : 'Discriminated2';
    }
}
