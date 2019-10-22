<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PrePersistEvent extends ModelEvent
{
    public const NAME = 'model.pre_persist';
}
