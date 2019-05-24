<?php

namespace JPC\Test\MongoDB\ODM\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

class Discriminated2
{
    /**
     * @ODM\Field("type")
     */
    private $type;

    /**
     * @ODM\Field("field_disc_2")
     */
    private $fieldDisc2;

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of fieldDisc2
     */
    public function getFieldDisc2()
    {
        return $this->fieldDisc2;
    }

    /**
     * Set the value of fieldDisc2
     *
     * @return  self
     */
    public function setFieldDisc2($fieldDisc2)
    {
        $this->fieldDisc2 = $fieldDisc2;

        return $this;
    }
}
