<?php

namespace JPC\Test\MongoDB\ODM\GridFS\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;
use JPC\MongoDB\ODM\GridFS\Annotations\Mapping as GFS;
use JPC\MongoDB\ODM\GridFS\Document;

/**
 * @GFS\Document("gridfs_mapping")
 */
class GridFSObjectMapping extends Document
{

    /**
     * @ODM\Field("simple_metadata")
     * @GFS\Metadata
     */
    private $simpleMetadata;

    /**
     * Gets the value of simpleMetadata.
     *
     * @return mixed
     */
    public function getSimpleMetadata()
    {
        return $this->simpleMetadata;
    }

    /**
     * Sets the value of simpleMetadata.
     *
     * @param mixed $simpleMetadata the simple metadata
     *
     * @return self
     */
    public function setSimpleMetadata($simpleMetadata)
    {
        $this->simpleMetadata = $simpleMetadata;

        return $this;
    }
}
