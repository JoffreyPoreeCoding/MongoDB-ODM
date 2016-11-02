<?php

use JPC\MongoDB\ODM\Annotations\GridFS as GFS;

/**
 * @GFS\GridFSDocument("gridfs_test")
 */
class GridFSFile {
    
    /**
     * @Field
     */
    private $id;
}
