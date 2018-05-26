<?php

namespace JPC\MongoDB\ODM\Id;

use JPC\MongoDB\ODM\DocumentManager;
use MongoDB\BSON\ObjectId;

class DefaultGenerator {

    public function generate(DocumentManager $dm, $document){
        return new ObjectId();
    }
}