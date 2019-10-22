<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PreInsertEvent extends ModelEvent
{
    public const NAME = 'model.pre_insert';
}
