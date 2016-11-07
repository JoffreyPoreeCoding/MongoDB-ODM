<?php

namespace JPC\MongoDB\ODM\Annotations\Mapping;

/**
 * Annotation for map a document in MongoDB
 * 
 * @param   string  $collectionName     Name of the collection by default for the document
 * @param   string  $repositoryClass    Class containing repository of document
 * 
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
 * Annotation to map '_id' field on a property
 * 
 * @param   string  $name               Name of the field in MongoDB document
 * 
 * @Annotation
 * @Target("PROPERTY")
 */
class Id {
    
}

/**
 * Annotation for map a field of document on PHP attribut
 * 
 * @param   string  $name               Name of the field in MongoDB document
 * 
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
 * Map a single embedded document field
 * 
 * @param   string  $document           Class corresponding to embedded document
 * 
 * @Annotation
 * @Target("PROPERTY")
 */
class EmbeddedDocument {
    
    /**
     * @Required
     * @var string 
     */
    public $document;
}

/**
 * Map a multiple embedded document field
 * 
 * @param   string  $document           Class corresponding to embedded document
 * 
 * @Annotation
 * @Target("PROPERTY")
 */
class MultiEmbeddedDocument {
    
    /**
     * @Required
     * @var string 
     */
    public $document;
}