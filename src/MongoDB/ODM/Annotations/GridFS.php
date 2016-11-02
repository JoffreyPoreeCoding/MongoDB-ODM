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
 * @Target("METHOD")
 */
class File {}
