<?php

namespace JPC\MongoDB\ODM\Annotations\Event;

interface Event {
	public function getName();
}

/**
 * @Annotation
 * @Target("CLASS")
 */
class HasLifecycleCallbacks {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostLoad implements Event {
	public function getName(){
		return "post_load";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PrePersist implements Event {
	public function getName(){
		return "pre_persist";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostPersist implements Event {
	public function getName(){
		return "post_persist";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreFlush implements Event {
	public function getName(){
		return "pre_flush";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostFlush implements Event {
	public function getName(){
		return "post_flush";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreInsert implements Event {
	public function getName(){
		return "pre_insert";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostInsert implements Event {
	public function getName(){
		return "post_insert";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreUpdate implements Event {
	public function getName(){
		return "pre_update";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostUpdate implements Event {
	public function getName(){
		return "post_update";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreDelete implements Event {
	public function getName(){
		return "pre_delete";
	}
}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostDelete implements Event {
	public function getName(){
		return "post_delete";
	}
}
