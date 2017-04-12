<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata\Info;

class ReferenceInfo {

	private $isMultiple = false;

	private $document;

	private $collection;

    /**
     * Gets the value of isMultiple.
     *
     * @return mixed
     */
    public function getIsMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * Sets the value of isMultiple.
     *
     * @param mixed $isMultiple the is multiple
     *
     * @return self
     */
    public function setIsMultiple($isMultiple)
    {
        $this->isMultiple = $isMultiple;

        return $this;
    }

    /**
     * Gets the value of document.
     *
     * @return mixed
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Sets the value of document.
     *
     * @param mixed $document the document
     *
     * @return self
     */
    public function setDocument($document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * Gets the value of collection.
     *
     * @return mixed
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Sets the value of collection.
     *
     * @param mixed $collection the collection
     *
     * @return self
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this;
    }
}