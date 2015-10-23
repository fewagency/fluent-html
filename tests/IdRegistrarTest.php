<?php

use FewAgency\FluentHtml\IdRegistrar;

class IdRegistrarTest extends PHPUnit_Framework_TestCase
{
    public function testExists()
    {
        $r = new IdRegistrar();

        $this->assertFalse($r->exists('a'));

        $r->unique('a');

        $this->assertTrue($r->exists('a'));
    }

    public function testUnique()
    {
        $r = new IdRegistrar();

        $this->assertEquals('a', $r->unique('a'));
        $this->assertEquals('a2', $r->unique('a'));
        $this->assertEquals('a1', $r->unique('a1'));
        $this->assertEquals('a3', $r->unique('a3'));
        $this->assertEquals('a4', $r->unique('a3'));
        $this->assertEquals('a11', $r->unique('a11'));
        $this->assertEquals('a12', $r->unique('a11'));
    }

    public function testUniqueIncludingNumber()
    {
        $r = new IdRegistrar();

        $this->assertEquals('a1a', $r->unique('a1a'));
        $this->assertEquals('a1a2', $r->unique('a1a'));
        $this->assertEquals('a1a3', $r->unique('a1a2'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUniqueEmpty()
    {
        $r = new IdRegistrar();

        //Empty parameter must throw an exception!
        $r->unique(0);
    }
}