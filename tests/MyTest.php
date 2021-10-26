<?php

require __DIR__ . '/../src/MyFile.php';

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testTest()
    {
        $c = new Test();
        $this->assertTrue($c->oui());
    }
}
