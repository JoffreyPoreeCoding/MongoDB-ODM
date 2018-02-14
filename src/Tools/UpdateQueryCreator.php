<?php

namespace JPC\MongoDB\ODM\Tools;

class UpdateQueryCreator {

	public function createUpdateQuery($old, $new, $prefix = ""){
		$update = [];

		foreach ($new as $key => $value) {
			if(array_key_exists($key, $old)){
				if(is_array($value) && strstr(key($value), '$') !== false){
					$update[key($value)][$prefix . $key] = $value[key($value)];
				}
				else if(is_array($value) && is_array($old[$key])){
					if(!empty($old[$key])){
						$embeddedUpdate = array_merge_recursive($update, $this->createUpdateQuery($old[$key], $value, $prefix . $key . "."));

						foreach($embeddedUpdate as $updateOperator => $value){
							if(!isset($update[$updateOperator])){
								$update[$updateOperator] = [];
							}
							$update[$updateOperator] += $value; 
						}
					} else {
						$update['$set'][$prefix . $key] = $value;
					}
				} else {
					if($value !== null && $value !== $old[$key]){
						$update['$set'][$prefix . $key] = $value;
					} else if ($value === null) {
						$update['$unset'][$prefix . $key] = 1;
					}
				}
			} else {
				if(is_array($value) && strstr(key($value), '$') === false){
					$embeddedQuery = $this->createUpdateQuery([], $value, $prefix . $key . ".");
					if(count($embeddedQuery)  == 1 && key($embeddedQuery) == '$set'){
						$update['$set'][$prefix . $key] = $value;
					} else {
						$update = array_merge_recursive($update, $embeddedQuery);
					}
				}
				else if(is_array($value) && strstr(key($value), '$') !== false){
					$update[key($value)][$prefix . $key] = $value[key($value)];
				} else {
					$update['$set'][$prefix . $key] = $value;
				}
			}

			unset($new[$key]);
			unset($old[$key]);
		}

		foreach(array_keys($old) as $key){
			$update['$unset'][$prefix . $key] = 1; 
		}

		return $update;
	}
}