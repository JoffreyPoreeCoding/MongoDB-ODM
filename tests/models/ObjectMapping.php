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
    private $simple;
}
