<?php

namespace JPC\MongoDB\ODM\Id;

use JPC\MongoDB\ODM\DocumentManager;

/**
 * Definition of id generator
 *
 * @abstract
 */
abstract class AbstractIdGenerator
{

    /**
     * Create an ID
     *
     * @param   DocumentManager     $dm         Document manager of document
     * @param   object              $document   Document on wich the ID will be created
     * @return  mixed                         The ID
     *
     * @abstract
     */
    abstract public function generate(DocumentManager $dm, $document);
}
