<?php

namespace JPC\MongoDB\ODM\Extension\IndexManagement;

use JPC\MongoDB\ODM\DocumentManager;

/**
 *
 */
class IndexManagementExtension
{
    public static function getMethodPrefix()
    {
        return 'im_';
    }

    public function __construct(DocumentManager $documentManager)
    {
        dump('extension loaded');
    }

    public function createIndexes()
    {
        dump('createIndexes');
    }
}
