<?php

namespace JPC\MongoDB\ODM\Annotations\Mapping\CollectionOption;

//use MongoDB\Driver\WriteConcern;

/**
 * @Annotation
 */
class WriteConcern {
    private $w;
    private $timeout;
    private $journal;
    
    public function __construct(array $values) {
        $default = [
            "w" => 1,
            "timeout" => 0,
            "journal" => true,
        ];
        
        $diffs = array_diff(array_keys($values), array_keys($default));
        
        if(!empty($diffs)){
            throw new \Doctrine\Common\Annotations\AnnotationException("Parameter '" . $diffs[0] . "' is not valid parameter. Accepted parameter are : 'w', 'timeout' and 'journal'.");
        }
        
        $finalValues = array_merge($default, $values);
        
        $this->w = $finalValues["w"];
        $this->timeout = $finalValues["timeout"];
        $this->journal = $finalValues["journal"];
    }
    
    public function getWriteConcern(){
        return new \MongoDB\Driver\WriteConcern($this->w, $this->timeout, $this->journal);
    }
}
