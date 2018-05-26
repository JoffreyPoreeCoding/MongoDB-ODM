<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\MongoDB\ODM\Tools\ArrayModifier;
use JPC\Test\MongoDB\ODM\Framework\TestCase;

class ArrayModifierTest extends TestCase
{

    /**
     * @test
     */
    public function clearNullValues()
    {
        $array = [
            "my_value" => null,
            "my_embedded" => [
                "my_null_value" => null,
            ],
        ];
        ArrayModifier::clearNullValues($array);
        $this->assertEmpty($array);
    }

    /**
     * @test
     */
    public function aggregate()
    {
        $array = [
            "value" => "value",
            "embedded" => [
                "value" => "value",
            ],
            "multi_embedded" => [
                "first" => "value",
                "second" => [
                    "another_embedded" => "embedded",
                ],
            ],
            "special" => "special_value",
            "embbeded_with_special" => [
                "special" => "another_special_value",
            ],
        ];

        $result = ArrayModifier::aggregate($array, [
            "special" => function ($prefix, $key, $value, $new) {
                if (!empty($prefix)) {
                    $prefix .= ".";
                }
                $new[$prefix . $key] = $key . $value;
                return $new;
            },
        ]);

        $expected = [
            "value" => "value",
            "embedded.value" => "value",
            "multi_embedded.first" => "value",
            "multi_embedded.second.another_embedded" => "embedded",
            "special" => "specialspecial_value",
            "embbeded_with_special.special" => "specialanother_special_value",
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function disaggregate()
    {
        $array = [
            "value" => "value",
            "embedded.value" => "value",
            "multi_embedded.first" => "value",
            "multi_embedded.second.another_embedded" => "embedded",
            "special" => "special_value",
            "embbeded_with_special.special" => "another_special_value",
        ];

        $result = ArrayModifier::disaggregate($array);

        $expected = [
            "value" => "value",
            "embedded" => [
                "value" => "value",
            ],
            "multi_embedded" => [
                "first" => "value",
                "second" => [
                    "another_embedded" => "embedded",
                ],
            ],
            "special" => "special_value",
            "embbeded_with_special" => [
                "special" => "another_special_value",
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
