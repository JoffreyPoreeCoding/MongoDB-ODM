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

    public function unhydrate($object) {
        $datas = parent::unhydrate($object);
        
        $properties = $this->classMetadata->getPropertiesInfos();

        foreach ($properties as $name => $infos) {
            if($infos->getMetadata()){
                if(isset($datas[$name])){
                    $datas["metadata"][$name] = $datas[$name];
                    unset($datas[$name]);
                }
            }
        }

        return $datas;
    }

}
