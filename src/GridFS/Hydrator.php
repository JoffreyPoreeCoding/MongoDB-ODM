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
    public function hydrate(&$object, $datas, $soft = false, $maxReferenceDepth = 10)
    {
        $stream = $object->getStream();
        if (isset($datas['stream'])) {
            $stream = $datas['stream'];
            unset($datas['stream']);
        }

        if (isset($datas["metadata"])) {
            $metadata = $datas["metadata"];
            $datas = array_merge((array) $datas, (array) $metadata);
            unset($datas["metadata"]);
        }
        parent::hydrate($object, $datas, $soft, $maxReferenceDepth);

        if (isset($stream)) {
            $object->setStream($stream);
        }
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
