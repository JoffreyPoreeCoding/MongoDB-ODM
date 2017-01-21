<?php

namespace JPC\MongoDB\ODM\Tools\ClassMetadata;

class MethodInfo {
    private $isPreLoadEvent;
    private $isPostLoadEvent;
    private $isPrePersistEvent;
    private $isPostPersistEvent;
    private $isPreFlushEvent;
    private $isPostFlushEvent;
    private $isPreInsertEvent;
    private $isPostInsertEvent;
    private $isPreUpdateEvent;
    private $isPostUpdateEvent;
    private $isPreRemoveEvent;
    private $isPostRemoveEvent;
}
