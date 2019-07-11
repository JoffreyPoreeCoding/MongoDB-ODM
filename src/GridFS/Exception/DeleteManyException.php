<?php

namespace JPC\MongoDB\ODM\GridFS\Exception;

use JPC\MongoDB\ODM\Exception\Exception;

class DeleteManyException extends Exception
{

    public function __construct($code = 0, $previous = null)
    {
        parent::__construct("You can't remove multiple GridFS documents at same time...", $code, $previous);
    }
}
