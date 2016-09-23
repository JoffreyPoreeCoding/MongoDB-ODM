<?php

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * @ODM\Document("simple_doc")
 */
class SimpleDocument {
    
    /**
     * @ODM\Field(name="_id")
     */
    private $id;
    
    /**
     * @ODM\Field(name="attr_1")
     */
    private $attr1;
    
    /**
     * @ODM\Field(name="attr_2")
     * @var string
     */
    private $attr2;
    
    function getId() {
        return $this->id;
    }

    function getAttr1() {
        return $this->attr1;
    }

    function getAttr2() {
        return $this->attr2;
    }

    function setAttr1($attr1) {
        $this->attr1 = $attr1;
    }

    function setAttr2($attr2) {
        $this->attr2 = $attr2;
    }


}
