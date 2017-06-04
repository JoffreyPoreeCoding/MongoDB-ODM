<?php

namespace JPC\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Annotations\Event\Event;

class EventManager {

	private $events = [];

	public function add(Event $event, $method){
		if(!isset($this->events[$event->getName()])){
			$this->events[$event->getName()][] = $method;
		}
	}

	public function execute($eventName, $object){
		foreach($this->events[$eventName] as $method){
			call_user_func([$object, $method]);
		}
	}

	public function getEvents(){
		return $this->events;
	}
}