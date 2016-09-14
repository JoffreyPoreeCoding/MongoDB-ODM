<?php

namespace JPC\MongoDB\ODM\Exception;

use JPC\MongoDB\ODM\Exception\Exception;

class ModelNotFoundException extends Exception {
    public function __construct($modelName = "", $code = 0, \Exception $previous = null) {
        parent::__construct("Model '$modelName' not found in models paths.", $code, $previous);
    }

}
