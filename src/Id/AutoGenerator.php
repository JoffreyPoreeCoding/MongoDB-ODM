<?php

namespace JPC\MongoDB\ODM\Id;

use JPC\MongoDB\ODM\DocumentManager;
use MongoDB\BSON\ObjectId;

/**
 * Id Generator for MongoDB\BSON\ObjectID
 */
class AutoGenerator
{

    /**
     * Create a MongoDB\BSON\ObjectId
     *
     * @param   DocumentManager     $dm         Document manager of document
     * @param   object              $document   Document on wich the ID will be created
     * @return  ObjectId                        The ID
     */
    public function generate(DocumentManager $dm, $document)
    {
        return new ObjectId();
    }
}
