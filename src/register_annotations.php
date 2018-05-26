<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerFile(__DIR__ . "/Annotations/Mapping.php");
AnnotationRegistry::registerFile(__DIR__ . "/GridFS/Annotations/Mapping.php");
AnnotationRegistry::registerFile(__DIR__ . "/Annotations/CollectionOption.php");
AnnotationRegistry::registerFile(__DIR__ . "/Annotations/Event.php");
