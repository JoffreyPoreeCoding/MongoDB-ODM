<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

/**
 * Description of FileInfos
 *
 * @author JoffreyP
 */
class FileInfos {
    
    /**
     * @ODM\Field("filename")
     */
    private $filename;
    
    /**
     * @ODM\Field("filename")
     */
    private $aliases;
    
    /**
     * @ODM\Field("chunckSize")
     */
    private $chunckSize;
    
    /**
     * @ODM\Field("uploadDate")
     */
    private $uploadDate;
    
    /**
     * @ODM\Field("length")
     */
    private $length;
    
    /**
     * @ODM\Field("contentType")
     */
    private $contentType;
    
    /**
     * @ODM\Field("md5")
     */
    private $md5;
    
    public function getFilename() {
        return $this->filename;
    }

    public function getAliases() {
        return $this->aliases;
    }

    public function getChunckSize() {
        return $this->chunckSize;
    }

    public function getUploadDate() {
        return $this->uploadDate;
    }

    public function getLength() {
        return $this->length;
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function getMd5() {
        return $this->md5;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }

    public function setAliases($aliases) {
        $this->aliases = $aliases;
    }

    public function setChunckSize($chunckSize) {
        $this->chunckSize = $chunckSize;
    }

    public function setUploadDate($uploadDate) {
        $this->uploadDate = $uploadDate;
    }

    public function setLength($length) {
        $this->length = $length;
    }

    public function setContentType($contentType) {
        $this->contentType = $contentType;
    }

    public function setMd5($md5) {
        $this->md5 = $md5;
    }
}
