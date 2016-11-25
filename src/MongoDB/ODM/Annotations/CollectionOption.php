<?php

namespace JPC\MongoDB\ODM\Annotations\Mapping\CollectionOption;

/**
 * @Annotation
 * @Target("ANNOTATION")
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

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class ReadConcern {
    
    private $level;
    
    public function __construct(array $values) {
        if(isset($values["value"]) && !isset($values["level"])){
            $values["level"] = $values["value"];
        }
        
        $expected = [\MongoDB\Driver\ReadConcern::LOCAL, \MongoDB\Driver\ReadConcern::MAJORITY];
        if(!isset($values["level"]) || !in_array($values["level"], $expected)){
            throw new \JPC\MongoDB\ODM\Exception\AnnotationException("level value could only be '". \MongoDB\Driver\ReadConcern::LOCAL . "' or '" . \MongoDB\Driver\ReadConcern::MAJORITY . "'.");
        }
        
        $this->level = $values["level"];
    }
    
    public function getReadConcern(){
        return new \MongoDB\Driver\ReadConcern($this->level);
    }
}

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class ReadPreference {
    
    /**
     * @Enum({
     * 
     * })
     * @var type 
     */
    public $mode;
    
    public $tagset;
    
//    public function __construct(array $values) {
//        if(isset($values["value"]) && !isset($values["level"])){
//            $values["level"] = $values["value"];
//        }
//        
//        $expected = [\MongoDB\Driver\ReadConcern::LOCAL, \MongoDB\Driver\ReadConcern::MAJORITY];
//        if(!in_array($values["level"], $expected)){
//            throw new \JPC\MongoDB\ODM\Exception\AnnotationException("level value could only be '". \MongoDB\Driver\ReadConcern::LOCAL . "' or '" . \MongoDB\Driver\ReadConcern::MAJORITY . "'.");
//        }
//        
//        $this->level = $values["level"];
//    }
    
    public function getReadConcern(){
        return new \MongoDB\Driver\ReadConcern($this->level);
    }
}
