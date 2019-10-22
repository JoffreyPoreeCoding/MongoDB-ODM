<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PreDeleteEvent extends ModelEvent
{
    public const NAME = 'model.pre_delete';
}
