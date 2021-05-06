<?php

namespace JPC\MongoDB\ODM\Annotations\Mapping;

/**
 * Annotation for map a document in MongoDB
 *
 * @param   string  $collectionName     Name of the collection by default for the document
 * @param   string  $repositoryClass    Class containing repository of document
 *
 * @Annotation
 * @Target("CLASS")
 */
class Document
{

    /**
     * @Required
     * @var string
     */
    public $collectionName;

    /**
     * @var string
     */
    public $repositoryClass = null;

    /**
     * @var string
     */
    public $hydratorClass = null;

    /**
     * @var bool
     */
    public $capped = false;

    /**
     * @var int
     */
    public $size = 536900000;

    /**
     * @var int
     */
    public $max = false;

    /**
     * @var bool
     */
    public $bypassConstructorOnFind = false;
}

/**
 * Option for collection reading/writing
 *
 * @param   string  $collectionName     Name of the collection by default for the document
 * @param   string  $repositoryClass    Class containing repository of document
 * @param   string  $repositoryClass    Class containing repository of document
 * @param   string  $repositoryClass    Class containing repository of document
 *
 * @Annotation
 * @Target("CLASS")
 */
class Option
{
    public $readConcern;
    public $readPreference;
    public $typeMap;
    public $writeConcern;
}

/**
 * Annotation to map '_id' field on a property
 *
 * @param   string  $name               Name of the field in MongoDB document
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Id
{

    const NONE = null;
    const AUTO = 'JPC\MongoDB\ODM\Id\AutoGenerator';

    public $generator = self::NONE;
}

/**
 * Annotation for map a field of document on PHP attribut
 *
 * @param   string  $name               Name of the field in MongoDB document
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Field
{

    /**
     * @Required
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $index;
}

/**
 * Map a single embedded document field
 *
 * @param   string  $document           Class corresponding to embedded document
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class EmbeddedDocument
{

    /**
     * @Required
     * @var string
     */
    public $document;
}

/**
 * Map a multiple embedded document field
 *
 * @param   string  $document           Class corresponding to embedded document
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class MultiEmbeddedDocument
{

    /**
     * @Required
     * @var string
     */
    public $document;
}

/**
 * Refer another document
 *
 * @param   string  $document           Class corresponding to refered document
 * @param   string  $collection         Collection where to find object
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class RefersOne
{
    public $document;
    public $collection;
}

/**
 * Refer another documents
 *
 * @param   string  $document           Class corresponding to refered document
 * @param   string  $collection         Collection where to find object
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class RefersMany
{
    public $document;
    public $collection;
}

/**
 * Field to use for choosing class of embedded document
 *
 * @param   string  $field  Field name
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class DiscriminatorField
{
    public $field;
}

/**
 * Array of [value => class] to determine wich value will instance the class
 *
 * @param   array  $map  Class map
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class DiscriminatorMap
{
    public $map;
}

/**
 * Method to determine wich class will be instanced
 *
 * @param   array   $method      Method name
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class DiscriminatorMethod
{
    public $method;
}
