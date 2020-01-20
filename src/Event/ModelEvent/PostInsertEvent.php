<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PostInsertEvent extends ModelEvent
{
    const NAME = 'model.post_insert';
}
