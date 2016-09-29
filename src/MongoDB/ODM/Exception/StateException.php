<?php

namespace JPC\MongoDB\ODM\Exception;


/**
 * Exception for when the document changing state in object manager
 */
class StateException extends Exception {
    
    /**
     * Create new StateException
     * 
     * @param   string      $message    Message to display in exception
     * @param   int         $code       Error code
     * @param   \Exception  $previous   Previous throwed exception
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        $message = !empty($message) ? $message : "You can't change state of a non persisted object";
        parent::__construct($message, $code, $previous);
    }
}

