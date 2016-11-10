<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerFile(__DIR__."/Annotations/Mapping.php");
AnnotationRegistry::registerFile(__DIR__."/Annotations/GridFS.php");
AnnotationRegistry::registerFile(__DIR__."/Annotations/CollectionOption.php");
