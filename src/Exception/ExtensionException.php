<?php

namespace JPC\MongoDB\ODM\Exception;


/**
 * Exception for when the document changing state in object manager
 */
class ExtensionException extends Exception {
    
    /**
     * Create new ExtensionException
     * 
     * @param   string      $message    Message to display in exception
     * @param   int         $code       Error code
     * @param   \Exception  $previous   Previous throwed exception
     */
    public function __construct($extension) {
        $message = 'Cannot find extension ' . $extension . ' please verify that class is loaded.';
        parent::__construct($message, $code, $previous);
    }
}