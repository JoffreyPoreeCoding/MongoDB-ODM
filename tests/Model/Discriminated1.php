<?php

namespace JPC\Test\MongoDB\ODM\Model;

use JPC\MongoDB\ODM\Annotations\Mapping as ODM;

class Discriminated1
{
    /**
     * @ODM\Field("type")
     */
    private $type;

    /**
     * @ODM\Field("field_disc_1")
     */
    private $fieldDisc1;

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
     * Get the value of fieldDisc1
     */
    public function getFieldDisc1()
    {
        return $this->fieldDisc1;
    }

    /**
     * Set the value of fieldDisc1
     *
     * @return  self
     */
    public function setFieldDisc1($fieldDisc1)
    {
        $this->fieldDisc1 = $fieldDisc1;

        return $this;
    }
}
