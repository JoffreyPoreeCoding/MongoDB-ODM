<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\GridFS\Annotations\Mapping as GFS;

/**
 * @GFS\Document("default")
 */
class Document
{

    /**
     * @ODM\Id
     */
    protected $id;

    /**
     * @GFS\Filename
     */
    protected $filename;

    /**
     * @GFS\Aliases
     */
    protected $aliases;

    /**
     * @GFS\ChunkSize
     */
    protected $chunkSize;

    /**
     * @GFS\UploadDate
     */
    protected $uploadDate;

    /**
     * @GFS\Length
     */
    protected $length;

    /**
     * @GFS\ContentType
     */
    protected $contentType;

    /**
     * @GFS\Md5
     */
    protected $md5;

    /**
     * @GFS\Stream
     */
    protected $stream;

    public function getId()
    {
        return $this->id;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function getMd5()
    {
        return $this->md5;
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function setStream($stream)
    {
        $this->stream = $stream;
        return $this;
    }
}
