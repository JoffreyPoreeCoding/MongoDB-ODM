<?php

public function getObjectChanges($object) {
        $new_datas = $this->hydrator->unhydrate($object);
        $old_datas = $this->uncacheObject($object);
        $changes = $this->compareDatas($new_datas, $old_datas);

        return $changes;
    }

    protected function compareDatas($new, $old) {
        $changes = [];
        foreach ($new as $key => $value) {
            if (is_array($old) && array_key_exists($key, $old) && $old[$key] !== null) {
                if (is_array($value) && is_array($old[$key])) {
                    $compare = true;
                    if (is_int(key($value))) {
                        $diff = array_diff_key($value, $old[$key]);
                        if (!empty($diff)) {
                            foreach ($diff as $diffKey => $diffValue) {
                                $changes[$key]['$push'][$diffKey] = $diffValue;
                            }
                            $compare = false;
                        }

                        $diff = array_diff_key($old[$key], $value);
                        if ($compare && !empty($diff)) {
                            foreach ($diff as $diffKey => $diffValue) {
                                $value[$diffKey] = null;
                            }
                        }
                    }

                    if ($compare) {
                        $array_changes = $this->compareDatas($value, $old[$key]);
                        if (!empty($array_changes)) {
                            $changes[$key] = $array_changes;
                        }
                    }
                } else if ($value != $old[$key] || $value !== $old[$key]) {
                    $changes[$key]['$set'] = $value;
                }
            } else if (is_array($old) && array_key_exists($key, $old) && $old[$key] === null) {
                if ($old[$key] != $value) {
                    if (is_array($value) && is_int(key($value))) {
                        $changes[$key]['$push'] = $value;
                    } else if ($value === null && isset($old[$key])) {
                        $changes['$unset'][$key] = $value;
                    } else if (!isset($old[$key]) && is_array($value)) {
                        $changes[$key]['$set'] = Tools\ArrayModifier::clearNullValues($value);
                    } else if (!isset($old[$key])) {
                        $changes[$key]['$set'] = $value;
                    }
                }
            } else {
                if (is_array($value) && is_int(key($value)) && !isset($old)) {
                    $changes[$key]['$push'] = $value;
                } else if ($old != $value) {
                    if ($value === null) {
                        $changes['$unset'][$key] = $value;
                    } else if (!isset($old)) {
                        $changes[$key]['$set'] = $value;
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Insert object into mongoDB
     * 
     * @param   mixed       $object     Object to insert
     */
    private function insert($collection, $objects) {
        if ($pos = strpos($collection, ".files")) {
            $collection = substr($collection, 0, $pos);
        }
        $rep = $this->getRepository(get_class($objects[key($objects)]), $collection);
        $hydrator = $rep->getHydrator();

        if (is_a($rep, "JPC\MongoDB\ODM\GridFS\Repository")) {
            $bucket = $rep->getBucket();

            foreach ($objects as $obj) {
                $datas = $hydrator->unhydrate($obj);
                $stream = $datas["stream"];

                $options = $datas;
                unset($options["stream"]);
                if (isset($datas["_id"]) && $datas["_id"] == null || !isset($datas["_id"])) {
                    unset($options["_id"]);
                }

                $filename = isset($options["filename"]) && null != $datas["filename"] ? $datas["filename"] : md5(uniqid());

                if (isset($options["filename"])) {
                    unset($options["filename"]);
                }

                if (isset($options["metadata"])) {
                    foreach ($options["metadata"] as $key => $value) {
                        if (null === $value) {
                            unset($options["metadata"][$key]);
                        }
                    }

                    if (empty($options["metadata"])) {
                        unset($options["metadata"]);
                    }
                }

                $id = $bucket->uploadFromStream($filename, $stream, $options);
                if($this->debug)
                    $this->logger->debug("Inserted object into GridFS with id '$id'");

                $hydrator->hydrate($obj, ["_id" => $id]);
                $this->objectManager->setObjectState($obj, ObjectManager::OBJ_MANAGED);
                $this->refresh($obj);
            }
        } else {
            $collection = $rep->collection;

            $datas = [];
            foreach ($objects as $object) {
                $datas[] = $hydrator->unhydrate($object);
                Tools\ArrayModifier::clearNullValues($datas);
            }

            $res = $collection->insertMany($datas);


            if ($res->isAcknowledged()) {
                foreach ($res->getInsertedIds() as $index => $id) {
                    if($this->debug)
                        $this->logger->debug("Inserted object into MongoDB, collection '" . $collection->getCollectionName() . "', with id '$id'");
                    $hydrator->hydrate($objects[$index], ["_id" => $id]);
                    $this->objectManager->setObjectState($objects[$index], ObjectManager::OBJ_MANAGED);
                    $this->refresh($objects[$index]);
                    $rep->cacheObject($objects[$index]);
                }
            }
        }
    }

    /**
     * Update object into mongoDB
     * 
     * @param   mixed       $object     Object to update
     */
    private function update($collection, $object) {
        $rep = $this->getRepository(get_class($object), $collection);
        $collection = $rep->collection;

        $diffs = $rep->getObjectChanges($object);
        $update = $this->createUpdateQueryStatement($diffs);

        $hydrator = $rep->getHydrator();

        $id = $hydrator->unhydrate($object)["_id"];

        if (is_array($id)) {
            $id = Tools\ArrayModifier::clearNullValues($id);
        }

        if (!empty($update)) {
            if($this->debug)
                $this->logger->debug("Update object with id '$id', see metadata for update query", ["update_query" => $update]);
            $res = $collection->updateOne(["_id" => $id], $update);
            if ($res->isAcknowledged()) {
                $this->refresh($object);
                $rep->cacheObject($object);
            }
        }
    }

    /**
     * Remove object from MongoDB
     * 
     * @param   mixed       $object     Object to insert
     */
    private function doRemove($collection, $object) {
        if (false != ($pos = strpos($collection, ".files"))) {
            $collection = substr($collection, 0, $pos);
        }

        $rep = $this->getRepository(get_class($object), $collection);

        $unhydrated = $rep->getHydrator()->unhydrate($object);
        $id = $unhydrated["_id"];
        if (is_array($id)) {
            $id = Tools\ArrayModifier::clearNullValues($id);
        }

        if ($rep instanceof GridFS\Repository) {
            fclose($unhydrated["stream"]);
            $this->objectManager->removeObject($object);
            if($this->debug)
                $this->logger->debug("Delete object in bucket '".$rep->getBucket()->getBucketName()."' with id '$id'");
            $rep->getBucket()->delete($id);
        } else {
            $res = $rep->collection->deleteOne(["_id" => $id]);

            if ($res->isAcknowledged()) {
                if($this->debug)
                    $this->logger->debug("Delete object in collection '".$rep->collection->getCollectionName()."' with id '".(string) $id."'");
                $this->objectManager->removeObject($object);
            } else {
                if($this->debug)
                    $this->logger->error("Can't delete document in '".$rep->collection->getCollectionName()."' with id '".(string) $id."'");
                throw new \Exception("Error on removing the document with _id : " . (string) $id . "in collection " . $collection);
            }
        }
    }

    /* ================================== */
    /*       THIS FUNCTIONS WIIL BE       */
    /*        MODIFIED AND UPDATED        */
    /* ================================== */

    private function createUpdateQueryStatement($datas) {
        $update = [];

        $update['$set'] = [];
        foreach ($datas as $key => $value) {
            $push = null;
            if ($key == '$set') {
                $update['$set'] += $value;
            } else if (is_array($value)) {
                $push = $this->checkPush($value, $key);
                if ($push != null) {
                    foreach ($push as $field => $fieldValue) {
                        $update['$push'][$field] = ['$each' => $fieldValue];
                    }
                }
                $inc = $this->checkInc($value, $key);
                if ($inc != null) {
                    foreach ($inc as $field => $fieldValue) {
                        $update['$inc'][$field] = $fieldValue;
                    }
                }
                $update['$set'] += Tools\ArrayModifier::aggregate($value, [
                    '$set' => [$this, 'onAggregSet'],
                    ], $key);
            } else {
                $update['$set'][$key] = $value;
            }
        }

        foreach ($update['$set'] as $key => $value) {
            if (strstr($key, '$push')) {
                unset($update['$set'][$key]);
            }

            if (array_key_exists($key, $update['$set']) && $update['$set'][$key] === null) {
                unset($update['$set'][$key]);
                $update['$unset'][$key] = "";
            }
        }

        if (isset($update['$inc'])) {
            foreach ($update['$inc'] as $key => $value) {
                unset($update['$set'][$key]);
            }
        }

        if (empty($update['$set'])) {
            unset($update['$set']);
        }

        return $update;
    }

    private function checkPush($array, $prefix = '') {
        foreach ($array as $key => $value) {
            if ($key === '$push') {
                $return = [];
                foreach ($value as $toPush) {
                    $return[] = $toPush;
                }
                return [$prefix => $return];
            } else if (is_array($value)) {
                if (null != ($push = $this->checkPush($value, $prefix . '.' . $key))) {
                    return $push;
                }
            }
        }
    }

    private function checkInc($array, $prefix = '') {
        foreach ($array as $key => $value) {
            if ($key == '$set') {
                $key = "";
            }
            if ($key === '$inc') {
                $return = $value;
                return [$prefix => $return];
            } else if (is_array($value)) {
                if ($key != '') {
                    $prefix = $prefix . '.' . $key;
                }
                if (null != ($inc = $this->checkInc($value, $prefix))) {
                    return $inc;
                }
            }
        }
    }

    public function onAggregSet($prefix, $key, $value, $new) {
        if (is_array($value)) {
            foreach ($value as $k => $val) {
                $new[$prefix][$k] = $val;
            }
        } else {
            $new[$prefix] = $value;
        }

        return $new;
    }

    public function onAggregInc($prefix, $key, $value, $new) {
        $new[$prefix] = [$key => $value];

        return $new;
    }