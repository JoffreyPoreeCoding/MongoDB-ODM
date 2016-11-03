<?php

namespace JPC\MongoDB\ODM\Annotations\GridFS;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Document {
    
    /**
     * @Required
     */
    public $bucketName;
    public $repositoryClass;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class File {}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Stream {}
