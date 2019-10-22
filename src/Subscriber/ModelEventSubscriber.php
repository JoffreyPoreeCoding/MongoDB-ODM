<?php

namespace JPC\MongoDB\ODM\Subscriber;

use JPC\MongoDB\ODM\Event\ModelEvent\ModelEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostDeleteEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostFlushEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostInsertEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostLoadEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostPersistEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PostUpdateEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreDeleteEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreFlushEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreInsertEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PrePersistEvent;
use JPC\MongoDB\ODM\Event\ModelEvent\PreUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ModelEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PostDeleteEvent::NAME => 'onModelEvent',
            PostFlushEvent::NAME => 'onModelEvent',
            PostInsertEvent::NAME => 'onModelEvent',
            PostLoadEvent::NAME => 'onModelEvent',
            PostPersistEvent::NAME => 'onModelEvent',
            PostUpdateEvent::NAME => 'onModelEvent',
            PreDeleteEvent::NAME => 'onModelEvent',
            PreFlushEvent::NAME => 'onModelEvent',
            PreInsertEvent::NAME => 'onModelEvent',
            PrePersistEvent::NAME => 'onModelEvent',
            PreUpdateEvent::NAME => 'onModelEvent',
        ];
    }

    public function onModelEvent(ModelEvent $event)
    {
        $classMetadata = $event->getRepository()->getClassMetadata();

        $modelEvents = $classMetadata->getEvents();
        $currentModelEvents = $modelEvents[$event::NAME] ?? [];

        $document = $event->getDocument();
        if(isset($document)){
            while(false !== ($modelEvent = current($currentModelEvents)) && !$event->isPropagationStopped()){
                call_user_func([$document, $modelEvent], $event);
                next($currentModelEvents);
            }
        }

        return $event;
    }
}
