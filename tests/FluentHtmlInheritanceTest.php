<?php
use FewAgency\FluentHtml\Testing\ComparesFluentHtml;
use FewAgency\FluentHtml\Testing\FluentTestInheritorElementA;

class FluentHtmlInheritanceTest extends PHPUnit_Framework_TestCase
{
    use ComparesFluentHtml;

    public function testSubclassInstantiation()
    {
        $e = new FluentTestInheritorElementA();

        $this->assertHtmlEquals('A content B content <p>B sub content</p>', $e);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreatingMissingSubclass()
    {
        (new FluentTestInheritorElementA())->createMissingSubclass();
    }
}