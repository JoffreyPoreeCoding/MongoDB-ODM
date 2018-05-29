<?php

namespace JPC\MongoDB\ODM\Annotations\Event;

/**
 * Event definition
 */
interface Event
{
    public function getName();
}

/**
 * Inform that model has events
 * @Annotation
 * @Target("CLASS")
 */
class HasLifecycleCallbacks
{
    
}

/**
 * Executed after document loaded (after find, findBy, etc...)
 * @Annotation
 * @Target("METHOD")
 */
class PostLoad implements Event
{
    public function getName()
    {
        return "post_load";
    }
}

/**
 * Executed before document persisted
 * @Annotation
 * @Target("METHOD")
 */
class PrePersist implements Event
{
    public function getName()
    {
        return "pre_persist";
    }
}

/**
 * Executed after document persisted
 * @Annotation
 * @Target("METHOD")
 */
class PostPersist implements Event
{
    public function getName()
    {
        return "post_persist";
    }
}

/**
 * Executed before document flushed
 * @Annotation
 * @Target("METHOD")
 */
class PreFlush implements Event
{
    public function getName()
    {
        return "pre_flush";
    }
}

/**
 * Executed after document flushed
 * @Annotation
 * @Target("METHOD")
 */
class PostFlush implements Event
{
    public function getName()
    {
        return "post_flush";
    }
}

/**
 * Executed before document insertion
 * @Annotation
 * @Target("METHOD")
 */
class PreInsert implements Event
{
    public function getName()
    {
        return "pre_insert";
    }
}

/**
 * Executed after document insertion
 * @Annotation
 * @Target("METHOD")
 */
class PostInsert implements Event
{
    public function getName()
    {
        return "post_insert";
    }
}

/**
 * Executed before document update
 * @Annotation
 * @Target("METHOD")
 */
class PreUpdate implements Event
{
    public function getName()
    {
        return "pre_update";
    }
}

/**
 * Executed after document update
 * @Annotation
 * @Target("METHOD")
 */
class PostUpdate implements Event
{
    public function getName()
    {
        return "post_update";
    }
}

/**
 * Executed before document deletion
 * @Annotation
 * @Target("METHOD")
 */
class PreDelete implements Event
{
    public function getName()
    {
        return "pre_delete";
    }
}

/**
 * Executed after document deletion
 * @Annotation
 * @Target("METHOD")
 */
class PostDelete implements Event
{
    public function getName()
    {
        return "post_delete";
    }
}
