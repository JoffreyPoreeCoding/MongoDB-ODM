<?php

namespace JPC\Test\MongoDB\ODM\Tools;

use JPC\Test\MongoDB\ODM\Framework\TestCase;
use JPC\MongoDB\ODM\Tools\ArrayModifier;

class ArrayModifierTest extends TestCase {

    public function test_clearNullValues() {
        $array = [
            "my_value" => null,
            "my_embedded" => [
                "my_null_value" => null
            ]
        ];
        ArrayModifier::clearNullValues($array);
        $this->assertEmpty($array);
    }

    public function test_aggregate() {
        $array = [
            "value" => "value",
            "embedded" => [
                "value" => "value"
            ],
            "multi_embedded" => [
                "first" => "value",
                "second" => [
                    "another_embedded" => "embedded"
                ]
            ],
            "special" => "special_value",
            "embbeded_with_special" => [
                "special" => "another_special_value"
            ]
        ];

        $result = ArrayModifier::aggregate($array, [
                    "special" => function($prefix, $key, $value, $new) {
                        if (!empty($prefix)) {
                            $prefix .= ".";
                        }
                        $new[$prefix . $key] = $key . $value;
                        return $new;
                    }
        ]);

        $expected = [
            "value" => "value",
            "embedded.value" => "value",
            "multi_embedded.first" => "value",
            "multi_embedded.second.another_embedded" => "embedded",
            "special" => "specialspecial_value",
            "embbeded_with_special.special" => "specialanother_special_value"
        ];

        $this->assertEquals($expected, $result);
    }
    
    public function test_disaggregate(){
        $array = [
            "value" => "value",
            "embedded.value" => "value",
            "multi_embedded.first" => "value",
            "multi_embedded.second.another_embedded" => "embedded",
            "special" => "special_value",
            "embbeded_with_special.special" => "another_special_value"
        ];
        
        $result = ArrayModifier::disaggregate($array);
        
        $expected = [
            "value" => "value",
            "embedded" => [
                "value" => "value"
            ],
            "multi_embedded" => [
                "first" => "value",
                "second" => [
                    "another_embedded" => "embedded"
                ]
            ],
            "special" => "special_value",
            "embbeded_with_special" => [
                "special" => "another_special_value"
            ]
        ];
        
        $this->assertEquals($expected, $result);
    }

}
