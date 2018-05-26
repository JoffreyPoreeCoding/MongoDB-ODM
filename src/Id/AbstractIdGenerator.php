<?php

namespace JPC\MongoDB\ODM\Id;

use JPC\MongoDB\ODM\DocumentManager;

abstract class AbstractIdGenerator {

    public abstract function generate(DocumentManager $dm, $document);
}