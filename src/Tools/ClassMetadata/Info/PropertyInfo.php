<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata\Info;

/**
 * Store infos for given property
 */
class PropertyInfo
{
    private $field;
    private $embedded = false;
    private $multiEmbedded = false;
    private $embeddedClass;
    private $metadata = false;
    private $referenceInfo;
    private $discriminatorField;
    private $discriminatorMap;
    private $discriminatorMethod;

    public function getField()
    {
        return $this->field;
    }

    public function getEmbedded()
    {
        return $this->embedded;
    }

    public function getMultiEmbedded()
    {
        return $this->multiEmbedded;
    }

    public function getEmbeddedClass()
    {
        return $this->embeddedClass;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;
        return $this;
    }

    public function setMultiEmbedded($multiEmbedded)
    {
        $this->multiEmbedded = $multiEmbedded;
        return $this;
    }

    public function setEmbeddedClass($embeddedClass)
    {
        $this->embeddedClass = $embeddedClass;
        return $this;
    }

    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets the value of referenceInfo.
     *
     * @return mixed
     */
    public function getReferenceInfo()
    {
        return $this->referenceInfo;
    }

    /**
     * Sets the value of referenceInfo.
     *
     * @param mixed $referenceInfo the reference info
     *
     * @return self
     */
    public function setReferenceInfo($referenceInfo)
    {
        $this->referenceInfo = $referenceInfo;

        return $this;
    }

    /**
     * Check if property is discriminable
     */
    public function isDiscriminable()
    {
        return isset($this->discriminatorField) || isset($this->discriminatorMethod);
    }

    /**
     * Get the value of discriminatorField
     */
    public function getDiscriminatorField()
    {
        return $this->discriminatorField;
    }

    /**
     * Set the value of discriminatorField
     *
     * @return  self
     */
    public function setDiscriminatorField($discriminatorField)
    {
        $this->discriminatorField = $discriminatorField;

        return $this;
    }

    /**
     * Get the value of discriminatorMap
     */
    public function getDiscriminatorMap()
    {
        return $this->discriminatorMap;
    }

    /**
     * Set the value of discriminatorMap
     *
     * @return  self
     */
    public function setDiscriminatorMap($discriminatorMap)
    {
        $this->discriminatorMap = $discriminatorMap;

        return $this;
    }

    /**
     * Get the value of discriminatorMethod
     */
    public function getDiscriminatorMethod()
    {
        return $this->discriminatorMethod;
    }

    /**
     * Set the value of discriminatorMethod
     *
     * @return  self
     */
    public function setDiscriminatorMethod($discriminatorMethod)
    {
        $this->discriminatorMethod = $discriminatorMethod;

        return $this;
    }
}
