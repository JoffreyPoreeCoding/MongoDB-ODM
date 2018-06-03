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
}
