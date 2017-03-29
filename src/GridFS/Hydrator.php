<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\Hydrator as BaseHydrator;

/**
 * Description of Hydrator
 *
 * @author JoffreyP
 */
class Hydrator extends BaseHydrator {

    public function hydrate(&$object, $datas){
        if(isset($datas["metadata"])){
            parent::hydrate($object, $datas["metadata"]);
            unset($datas["metadata"]);
        }
        parent::hydrate($object, $datas);
    }

    public function unhydrate($object) {
        $datas = parent::unhydrate($object);
        
        $properties = $this->classMetadata->getPropertiesInfos();

        foreach ($properties as $name => $infos) {
            if($infos->getMetadata()){
                if(isset($datas[$infos->getField()])){
                    $datas["metadata"][$infos->getField()] = $datas[$infos->getField()];
                    unset($datas[$infos->getField()]);
                }
            }
        }

        return $datas;
    }

}
