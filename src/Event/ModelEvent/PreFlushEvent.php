<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PreFlushEvent extends ModelEvent
{
    const NAME = 'model.pre_flush';
}
