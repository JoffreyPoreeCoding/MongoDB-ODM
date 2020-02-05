<?php

namespace JPC\MongoDB\ODM\Query;

trait FilterableQuery
{
    protected $filter;

    public function getFilter()
    {
        if (!isset($this->filter)) {
            $this->beforeQuery();
        }
        return $this->filter;
    }
}
