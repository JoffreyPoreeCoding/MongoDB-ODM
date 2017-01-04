<?php

namespace JPC\MongoDB\ODM\Annotations\Event;

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreLoad {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostLoad {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PrePersist {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostPersist {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreFlush {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostFlush {}
/**
 * @Annotation
 * @Target("METHOD")
 */
class PreUpdate {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostUpdate {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PreRemove {}

/**
 * @Annotation
 * @Target("METHOD")
 */
class PostRemove {}