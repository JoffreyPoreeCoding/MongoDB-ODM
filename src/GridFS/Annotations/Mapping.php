<?php

namespace JPC\MongoDB\ODM\GridFS\Annotations\Mapping;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Document
{

    /**
     * @Required
     */
    public $bucketName;
    public $repositoryClass;
    public $hydratorClass = "JPC\MongoDB\ODM\GridFS\Hydrator";
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Filename
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Aliases
{
    
}
/**
 * @Annotation
 * @Target("PROPERTY")
 */
class ChunkSize
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class UploadDate
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Length
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class ContentType
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Md5
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Stream
{
    
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Metadata
{

}
