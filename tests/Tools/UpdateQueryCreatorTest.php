<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class UpdateQueryCreatorTest extends TestCase {

	public function test_createUpdateQuery(){
		$old = [
			"same" => "value",
			"different" => "value",
			"suppressed" => "value",
			"array_with_new" => [1,2],
			"array_same" => [1,2,3],
			"array_with_suppression" => [1,2,3],
			"set_key" => 1,
			"embedded" => [
				"same" => "value",
				"different" => "value",
				"array_with_new" => [1,2],
				"array_same" => [1,2,3],
				"array_with_suppression" => [1,2,3],
				"suppressed" => "value",
				"set_key" => 1
			]
		];

		$new = [
			"same" => "value",
			"different" => "new_value",
			"new" => "new_key",
			"array_with_new" => [1,2,3],
			"array_same" => [1,2,3],
			"array_with_suppression" => [1,2],
			"set_key" => ['$inc' => 1],
			"embedded" => [
				"same" => "value",
				"different" => "new_value",
				"array_with_new" => [1,2,3],
				"array_same" => [1,2,3],
				"array_with_suppression" => [1,2],
				"set_key" => ['$inc' => 1]
			]
		];

		$updateQueryCreator = new UpdateQueryCreator();

		$updateQuery = $updateQueryCreator->createUpdateQuery($old, $new);

		$expected = [
			'$set' => [
				"different" => "new_value",
				"array_with_new.2" => 3,
				"embedded.different" => "new_value",
				"embedded.array_with_new.2" => 3,
				"new" => "new_key"
			],
			'$unset' => [
				"suppressed" => 1,
				"array_with_suppression.2" => 1,
				"embedded.suppressed" => 1,
				"embedded.array_with_suppression.2" => 1
			],
			'$inc' => [
				"set_key" => 1,
				"embedded.set_key" => 1
			]
		];

		$this->assertEquals($expected, $updateQuery);
	}
}