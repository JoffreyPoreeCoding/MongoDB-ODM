<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM;

/**
 * Description of DocumentProxy
 *
 * @author JoffreyP
 */
class DocumentProxy {
    
    private $document;
    
    function __construct($document) {
        $this->document = $document;
    }
    
    public function __debugInfo() {
        return (array)$this->document;
    }
}
