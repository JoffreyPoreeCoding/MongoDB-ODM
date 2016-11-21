<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata;

class FieldInfo {
    private $field;
    private $embedded = false;
    private $multiEmbedded = false;
    private $embeddedClass;
    
    function getField() {
        return $this->field;
    }

    function getEmbedded() {
        return $this->embedded;
    }

    function getMultiEmbedded() {
        return $this->multiEmbedded;
    }

    function getEmbeddedClass() {
        return $this->embeddedClass;
    }

    function setField($field) {
        $this->field = $field;
        return $this;
    }

    function setEmbedded($embedded) {
        $this->embedded = $embedded;
        return $this;
    }

    function setMultiEmbedded($multiEmbedded) {
        $this->multiEmbedded = $multiEmbedded;
        return $this;
    }

    function setEmbeddedClass($embeddedClass) {
        $this->embeddedClass = $embeddedClass;
        return $this;
    }


}
