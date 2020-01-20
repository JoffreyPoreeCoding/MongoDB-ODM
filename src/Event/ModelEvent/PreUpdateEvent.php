<?php

namespace JPC\MongoDB\ODM\Event\ModelEvent;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;

class PreUpdateEvent extends ModelEvent
{
    const NAME = 'model.pre_update';
}
