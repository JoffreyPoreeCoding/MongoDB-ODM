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
        if (isset($datas["file"])) {
            $field = key($this->classMetadata->getPropertyWithAnnotation("JPC\MongoDB\ODM\Annotations\GridFS\FileInfos"));
            $prop = $this->classMetadata->getProperty($field);
            $prop->setAccessible(true);

            $prop->setValue($object, $this->convertEmbedded($datas["file"], "JPC\MongoDB\ODM\GridFS\FileInfos"));
        }

        if (isset($datas["stream"])) {
            $field = key($this->classMetadata->getPropertyWithAnnotation("JPC\MongoDB\ODM\Annotations\GridFS\Stream"));
            $prop = $this->classMetadata->getProperty($field);
            $prop->setAccessible(true);

            $prop->setValue($object, $datas["stream"]);
        }
        parent::hydrate($object, $datas);
    }

    /**
     * @TODO
     */
    public function unhydrate($object) {
        $datas = parent::unhydrate($object);

        foreach ($datas as $key => $value) {
            if ($key != "_id" || $value == null) {
                unset($datas[$key]);
                $datas["metadata"][$key] = $value;
            }
        }

        if (false !== ($file = $this->classMetadata->getPropertyWithAnnotation("JPC\MongoDB\ODM\Annotations\GridFS\FileInfos"))) {
            $field = key($file);
            $prop = $this->classMetadata->getProperty($field);
            $prop->setAccessible(true);
            $value = $prop->getValue($object);

            if (is_a($value, "JPC\MongoDB\ODM\GridFS\FileInfos")) {
                $hydrator = $this->getHydratorForEmbedded("JPC\MongoDB\ODM\GridFS\FileInfos");
                $value = $hydrator->unhydrate($value);
            }

            if ($value != null && is_array($value)) {
                $datas += $value;
            }
        }

        if (false !== ($file = $this->classMetadata->getPropertyWithAnnotation("JPC\MongoDB\ODM\Annotations\GridFS\Stream"))) {
            $field = key($file);
            $prop = $this->classMetadata->getProperty($field);
            $prop->setAccessible(true);
            $value = $prop->getValue($object);

            $datas["stream"] = $value;
        }

        return $datas;
    }

}
