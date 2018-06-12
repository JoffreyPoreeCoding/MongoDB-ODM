<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Tools\UpdateQueryCreator;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class UpdateQueryCreatorTest extends TestCase
{

    public function testCreateUpdateQuery()
    {
        $old = [
            "same" => "value",
            "different" => "value",
            "suppressed" => "value",
            "array_with_new" => [1, 2],
            "array_same" => [1, 2, 3],
            "array_with_suppression" => [1, 2, 3],
            "set_key" => 1,
            "embedded" => [
                "same" => "value",
                "different" => "value",
                "array_with_new" => [1, 2],
                "array_same" => [1, 2, 3],
                "array_with_suppression" => [1, 2, 3],
                "suppressed" => "value",
                "set_key" => 1,
                "embedded" => [
                    "same" => "value",
                    "different" => "value",
                    "array_with_new" => [1, 2],
                    "array_same" => [1, 2, 3],
                    "array_with_suppression" => [1, 2, 3],
                    "suppressed" => "value",
                    "set_key" => 1,
                ],
            ],
            "empty_array" => [],
        ];

        $new = [
            "same" => "value",
            "different" => "new_value",
            "new" => "new_key",
            "array_with_new" => [1, 2, 3],
            "array_same" => [1, 2, 3],
            "array_with_suppression" => [1, 2],
            "new_array" => [1, 2, 3],
            "set_key" => ['$inc' => 1],
            "embedded" => [
                "same" => "value",
                "different" => "new_value",
                "array_with_new" => [1, 2, 3],
                "array_same" => [1, 2, 3],
                "array_with_suppression" => [0 => 1, 2 => 3],
                "set_key" => ['$inc' => 1],
                "embedded" => [
                    "same" => "value",
                    "different" => "new_value",
                    "array_with_new" => [1, 2, 3],
                    "array_same" => [1, 2, 3],
                    "array_with_suppression" => [1, 2],
                    "set_key" => ['$inc' => 1],
                    "new_key" => ['$inc' => 1],
                ],
            ],
            "empty_array" => ['data' => 'value'],
        ];

        $updateQueryCreator = new UpdateQueryCreator();

        $updateQuery = $updateQueryCreator->createUpdateQuery($old, $new);

        $expected = [
            '$set' => [
                "different" => "new_value",
                "array_with_new.2" => 3,
                "array_with_suppression" => [1, 2],
                "embedded.different" => "new_value",
                "embedded.array_with_new.2" => 3,
                "embedded.array_with_suppression" => [1, 3],
                "embedded.embedded.different" => "new_value",
                "embedded.embedded.array_with_new.2" => 3,
                "embedded.embedded.array_with_suppression" => [1, 2],
                "new" => "new_key",
                "new_array" => [1, 2, 3],
                "empty_array" => ['data' => 'value'],
            ],
            '$unset' => [
                "suppressed" => 1,
                "embedded.suppressed" => 1,
                "embedded.embedded.suppressed" => 1,
            ],
            '$inc' => [
                "set_key" => 1,
                "embedded.set_key" => 1,
                "embedded.embedded.set_key" => 1,
                "embedded.embedded.new_key" => 1,
            ],
        ];

        $this->assertEquals($expected, $updateQuery);
    }

    /**
     * @test
     * Without old
     */
    public function createUpdateQueryWithoutOld()
    {
        $new = [
            "new" => "value",
            "inc" => ['$inc' => 1],
            "embedded" => [
                "new" => "value",
                "inc" => ['$inc' => 1],
                "embedded" => [
                    "new" => "value",
                    "inc" => ['$inc' => 1],
                ],
            ],
        ];

        $expected = [
            '$set' => [
                "new" => "value",
                "embedded.new" => "value",
                "embedded.embedded.new" => "value",
            ],
            '$inc' => [
                "inc" => 1,
                "embedded.inc" => 1,
                "embedded.embedded.inc" => 1,
            ],
        ];

        $updateQueryCreator = new UpdateQueryCreator();
        $updateQuery = $updateQueryCreator->createUpdateQuery([], $new);
        $this->assertEquals($expected, $updateQuery);
    }

    /**
     * @test
     */
    public function createUpdateQueryUnchangedArray()
    {
        $old = [
            'array' => [],
            'embedded' => [
                'array' => [
                    'array' => [],
                ],
            ],
        ];

        $new = [
            'array' => [],
            'embedded' => [
                'array' => [
                    'array' => [],
                ],
            ],
        ];

        $updateQueryCreator = new UpdateQueryCreator();
        $updateQuery = $updateQueryCreator->createUpdateQuery($old, $new);
        $this->assertEmpty($updateQuery);
    }

    /**
     * @test
     */
    public function createUpdateQueryWithNull()
    {
        $old = [
            'value' => null,
            'embedded' => [
                'array' => [
                    'value' => null,
                ],
                'value' => null
            ],
        ];

        $new = [
            'value' => null,
            'embedded' => [
                'array' => [
                    'value' => null,
                ],
                'value' => null
            ],
        ];

        $updateQueryCreator = new UpdateQueryCreator();
        $updateQuery = $updateQueryCreator->createUpdateQuery($old, $new);
        $this->assertEmpty($updateQuery);
    }
}
