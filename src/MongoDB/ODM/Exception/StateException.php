<?php

namespace JPC\MongoDB\ODM\Exception;

use JPC\MongoDB\ODM\Exception\Exception;

class StateException extends Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        $message = !empty($message) ? $message : "You can't change state of a non persisted object";
        parent::__construct($message, $code, $previous);
    }

}

