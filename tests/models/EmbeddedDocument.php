<?php

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("embedded_doc")
 */
class EmbeddedDocument {

    /**
     * @ODM\Field("_id")
     */
    private $id;

    /**
     * @ODM\Field("embedded_1")
     * @ODM\EmbeddedDocument("Embedded")
     */
    private $embedded;

    function getId() {
        return $this->id;
    }

    function getEmbedded() {
        return $this->embedded;
    }

    function setEmbedded($embedded) {
        $this->embedded = $embedded;
    }
}

class Embedded {

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
