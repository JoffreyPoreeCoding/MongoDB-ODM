<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\GridFS\Annotations\Mapping as GFS;
use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

class Document {
    
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
    
    function getId() {
        return $this->id;
    }

    function getFilename() {
        return $this->filename;
    }

    function getAliases() {
        return $this->aliases;
    }

    function getChunkSize() {
        return $this->chunkSize;
    }

    function getUploadDate() {
        return $this->uploadDate;
    }

    function getLength() {
        return $this->length;
    }

    function getContentType() {
        return $this->contentType;
    }

    function getMd5() {
        return $this->md5;
    }
    
    function getStream() {
        return $this->stream;
    }
    
    function setId($id) {
        $this->id = $id;
        return $this;
    }

    function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    function setStream($stream) {
        $this->stream = $stream;
        return $this;
    }
}
