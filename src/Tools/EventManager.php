<?php

namespace JPC\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Annotations\Event\Event;

class EventManager
{

    const EVENT_POST_LOAD = "post_load";
    const EVENT_PRE_PERSIST = "pre_persist";
    const EVENT_POST_PERSIST = "post_persist";
    const EVENT_PRE_FLUSH = "pre_flush";
    const EVENT_POST_FLUSH = "post_flush";
    const EVENT_PRE_INSERT = "pre_insert";
    const EVENT_POST_INSERT = "post_insert";
    const EVENT_PRE_UPDATE = "pre_update";
    const EVENT_POST_UPDATE = "post_update";
    const EVENT_PRE_DELETE = "pre_delete";
    const EVENT_POST_DELETE = "post_delete";

    private $events = [];

    public function add(Event $event, $method)
    {
        $this->events[$event->getName()][] = $method;
    }

    public function execute($eventName, $object)
    {
        if (isset($this->events[$eventName]) && is_array(($this->events[$eventName]))) {
            foreach ($this->events[$eventName] as $method) {
                call_user_func([$object, $method]);
            }
        }
    }

    public function getEvents()
    {
        return $this->events;
    }
}
