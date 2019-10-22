<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PostPersistEvent extends ModelEvent
{
    public const NAME = 'model.post_persist';
}
