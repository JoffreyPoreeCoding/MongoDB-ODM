<?php

namespace JPC\MongoDB\ODM\GridFS;

use JPC\MongoDB\ODM\Hydrator as BaseHydrator;

/**
 * Hydrator
 *
 * @author JoffreyP
 */
class Hydrator extends BaseHydrator
{

    /**
     * Hydrate an object from data
     *
     * @param   mixed   $object         Object to hydrate
     * @param   mixed   $datas          Data to hydrate object
     * @param   integer $maxReference   Depth Maximum reference depth
     * @return  void
     */
    public function hydrate(&$object, $datas, $maxReferenceDepth = 10)
    {
        if (isset($datas["metadata"])) {
            parent::hydrate($object, $datas["metadata"], $maxReferenceDepth);
            unset($datas["metadata"]);
        }
        parent::hydrate($object, $datas);
    }

    /**
     * Unhydrate object to array
     *
     * @param   mixed   $object     Object to unhydrate
     * @return  array
     */
    public function unhydrate($object)
    {
        $datas = parent::unhydrate($object);

        $properties = $this->classMetadata->getPropertiesInfos();

        foreach ($properties as $name => $infos) {
            if ($infos->getMetadata()) {
                if (isset($datas[$infos->getField()])) {
                    $datas["metadata"][$infos->getField()] = $datas[$infos->getField()];
                    unset($datas[$infos->getField()]);
                }
            }
        }
        return $datas;
    }
}
