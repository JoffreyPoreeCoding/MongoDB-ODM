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
    
    /**
     * @TODO
     */
    public function hydrate(&$object, $datas) {
        parent::hydrate($object, $datas);
    }

    /**
     * @TODO
     */
    public function unhydrate($object) {
        return parent::unhydrate($object);
    }

}
