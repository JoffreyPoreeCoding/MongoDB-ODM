<?php

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("multi_embedded_doc")
 */
class MultiEmbeddedDocument {
    /**
     * @ODM\Field("_id")
     */
    private $id;

    /**
     * @ODM\Field("multi_embedded")
     * @ODM\MultiEmbeddedDocument("MultiEmbedded")
     */
    private $multiEmbedded;

    function getId() {
        return $this->id;
    }

    function getMultiEmbedded() {
        return $this->multiEmbedded;
    }

    function addMultiEmbedded($embedded) {
        $this->multiEmbedded[] = $embedded;
        return $this;
    }
}

class MultiEmbedded {
    /**
     * @ODM\Field("attr_1")
     */
    private $attr1;

    /**
     * @ODM\Field("attr_2")
     */
    private $attr2;
    
    /**
     * @ODM\Field("embedded_1")
     * @ODM\EmbeddedDocument("EmbeddedTwo")
     */
    private $embedded;
    
    function getAttr1() {
        return $this->attr1;
    }

    function getAttr2() {
        return $this->attr2;
    }

    function getEmbedded(){
        return $this->embedded;
    }
    
    function setAttr1($attr1) {
        $this->attr1 = $attr1;
        return $this;
    }

    function setAttr2($attr2) {
        $this->attr2 = $attr2;
        return $this;
    }
    
    function setEmbedded($embedded){
        $this->embedded = $embedded;
        return $this;
    }
}

class EmbeddedTwo {

    /**
     * @ODM\Field("attr_1")
     */
    private $attr1;

    /**
     * @ODM\Field("attr_2")
     */
    private $attr2;
    
    function getAttr1() {
        return $this->attr1;
    }

    function getAttr2() {
        return $this->attr2;
    }

    function setAttr1($attr1) {
        $this->attr1 = $attr1;
        return $this;
    }

    function setAttr2($attr2) {
        $this->attr2 = $attr2;
        return $this;
    }
}