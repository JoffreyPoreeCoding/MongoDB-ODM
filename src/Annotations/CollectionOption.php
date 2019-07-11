<?php

namespace JPC\MongoDB\ODM\Annotations\CollectionOption;

/**
 * Write concern option for collection
 * @Annotation
 * @Target("ANNOTATION")
 */
class WriteConcern
{
    private $w;
    private $timeout;
    private $journal;

    public function __construct(array $values)
    {
        $default = [
            "w" => 1,
            "timeout" => null,
            "journal" => true,
        ];

        $diffs = array_diff(array_keys($values), array_keys($default));

        if (!empty($diffs)) {
            throw new \Doctrine\Common\Annotations\AnnotationException("Parameter '" . $diffs[0] . "' is not valid parameter. Accepted parameter are : 'w', 'timeout' and 'journal'.");
        }

        $finalValues = array_merge($default, $values);

        $this->w = $finalValues["w"];
        $this->timeout = $finalValues["timeout"];
        $this->journal = $finalValues["journal"];
    }

    public function getWriteConcern()
    {
        return new \MongoDB\Driver\WriteConcern($this->w, $this->timeout, $this->journal);
    }
}

/**
 * Read concern option for collection
 * @Annotation
 * @Target("ANNOTATION")
 */
class ReadConcern
{

    private $level;

    public function __construct(array $values)
    {
        if (isset($values["value"]) && !isset($values["level"])) {
            $values["level"] = $values["value"];
        }

        $expected = [\MongoDB\Driver\ReadConcern::LOCAL, \MongoDB\Driver\ReadConcern::MAJORITY];
        if (!isset($values["level"]) || !in_array($values["level"], $expected)) {
            throw new \JPC\MongoDB\ODM\Exception\AnnotationException("Level value could only be '" . \MongoDB\Driver\ReadConcern::LOCAL . "' or '" . \MongoDB\Driver\ReadConcern::MAJORITY . "'.");
        }

        $this->level = $values["level"];
    }

    public function getReadConcern()
    {
        return new \MongoDB\Driver\ReadConcern($this->level);
    }
}

/**
 * Read preference option for collection
 * @Annotation
 * @Target("ANNOTATION")
 */
class ReadPreference
{

    const READ_PREFERENCES_ALLOWED = [
        \MongoDB\Driver\ReadPreference::RP_PRIMARY,
        \MongoDB\Driver\ReadPreference::RP_PRIMARY_PREFERRED,
        \MongoDB\Driver\ReadPreference::RP_SECONDARY,
        \MongoDB\Driver\ReadPreference::RP_SECONDARY_PREFERRED,
        \MongoDB\Driver\ReadPreference::RP_NEAREST,
    ];

    private $mode;

    private $tagset = [];

    public function __construct(array $values)
    {
        if (isset($values["value"]) && !isset($values["mode"])) {
            $values["mode"] = $values["value"];
        }

        if (!isset($values["mode"]) || !in_array($values["mode"], self::READ_PREFERENCES_ALLOWED)) {
            $values = $prefix = "";
            foreach (self::READ_PREFERENCES_ALLOWED as $allowed) {
                $values .= $prefix . "'" . $allowed . "'";
                $prefix = ", ";
            }
            throw new \JPC\MongoDB\ODM\Exception\AnnotationException("Mode value could only be $values.");
        }

        $this->mode = $values["mode"];

        if (isset($values["tagset"]) && is_array($values["tagset"])) {
            $this->tagset = $values["tagset"];
        }
    }

    public function getReadPreference()
    {
        return new \MongoDB\Driver\ReadPreference($this->mode, $this->tagset);
    }
}
