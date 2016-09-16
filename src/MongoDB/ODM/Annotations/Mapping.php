<?php

namespace JPC\MongoDB\ODM\Annotations\Mapping;

/**
 * @Annotation 
 * @Target("CLASS")
 */
class Document {
    /**
     * @Required
     * @var string
     */
    public $collectionName;
    
    /**
     * @var string
     */
    public $repositoryClass = null;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Field {
    /**
     * @Required
     * @var string 
     */
    public $name;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class EmbeddedDocument {
    public $document;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class MultiEmbeddedDocument {
    public $document;
}