<?php

namespace JPC\MongoDB\ODM\Annotations\Mapping\CollectionOption;

//use MongoDB\Driver\WriteConcern;

/**
 * @Annotation
 */
class WriteConcern {
    private $wstring;
    private $wtimeout;
    private $journal;
    
    public function __construct(array $values) {
        $default = [
            "wstring" => 1,
            "wtimeout" => 0,
            "journal" => true,
        ];
        
        $diffs = array_diff(array_keys($values), array_keys($default));
        
        dump($diffs);
        
        $values = array_merge($default, $values);
        
        dump($values);
        
    }
}
