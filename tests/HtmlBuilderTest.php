<?php

use FewAgency\FluentHtml\HtmlBuilder;
use Illuminate\Support\Collection;

class HtmlBuilderTest extends PHPUnit_Framework_TestCase
{
    protected static function comparableHtml($html_string)
    {
        return str_replace("\n", ' ', $html_string);
    }

    public function testBuildHtmlElement()
    {
        $this->assertEquals(
            "<p id=\"a\" title=\"t\">\na\nb\n</p>",
            HtmlBuilder::buildHtmlElement('p', ['id' => 'a', 'title' => 't'], ['a', 'b'])
        );
    }

    public function testBuildHtmlElementSingleQuotedAttributes()
    {
        $this->assertEquals(
            "<p title='text'></p>",
            HtmlBuilder::buildHtmlElement('p', ['title' => 'text'], [], HtmlBuilder::DO_ESCAPE, "'")
        );
    }

    public function testBuildHtmlElementQuotesInAttribute()
    {
        $this->assertEquals(
            "<p title=\"&quot;t&#039;\"></p>",
            HtmlBuilder::buildHtmlElement('p', ['title' => '"t\''])
        );
    }

    public function testBuildHtmlElement0()
    {
        $this->assertEquals(
            "<p title=\"0\" accesskey=\"0\">\n0\n0\n</p>",
            HtmlBuilder::buildHtmlElement('p', ['title' => 0, 'accesskey' => '0'], [0, '0'])
        );
    }

    public function testBuildHtmlElementCollections()
    {
        $this->assertEquals(
            "<p id=\"a\" title=\"t\">\na\nb\n</p>",
            HtmlBuilder::buildHtmlElement('p',
                Collection::make(['id' => 'a', 'title' => 't']),
                Collection::make(['a', 'b']))
        );
    }

    public function testBuildHtmlElementMultilevel()
    {
        $this->assertEquals(
            "<p id=\"a\" title=\"0\">\na\n0\n</p>",
            HtmlBuilder::buildHtmlElement('p', [['id' => 'a'], [['title' => 0]]], [['a'], [[0]]])
        );
    }

    public function testBuildHtmlElementBooleans()
    {
        $this->assertEquals(
            "<textarea name=\"a\" title=\"t\" autofocus>\na\nb\n</textarea>",
            HtmlBuilder::buildHtmlElement('textarea',
                ['name' => 'a', 'title' => 't', 'autofocus' => true, 'disabled' => false],
                ['a', 'b' => true, 'c' => false]
            )
        );
    }

    public function testBuildHtmlElementCallable()
    {
        $this->assertEquals(
            "<p id=\"a\" class=\"a\" title=\"0\">\ntext\na\n0\n</p>",
            HtmlBuilder::buildHtmlElement('p',
                function () {
                    return [
                        'id' => 'a',
                        'class' => function () {
                            return 'a';
                        },
                        'title' => function () {
                            return 0;
                        }
                    ];
                },
                function () {
                    return [
                        "text",
                        function () {
                            return 'a';
                        },
                        function () {
                            return 0;
                        }
                    ];
                }
            )
        );
    }

    public function testBuildHtmlElementEscapingContent()
    {
        $this->assertEquals(
            "<p>&lt;br&gt;</p>",
            HtmlBuilder::buildHtmlElement('p', [], '<br>')
        );
    }

    public function testBuildHtmlElementNotEscapingContent()
    {
        $this->assertEquals(
            "<p><br></p>",
            HtmlBuilder::buildHtmlElement('p', [], '<br>', HtmlBuilder::DONT_ESCAPE)
        );
    }

    public function testBuildHtmlElementHtmlable()
    {
        $this->assertEquals(
            "<p><br></p>",
            HtmlBuilder::buildHtmlElement('p', [], new HtmlContent('<br>'))
        );
    }

    public function testBuildHtmlElementNonStringableObject()
    {
        $this->assertEquals(
            "<p></p>",
            HtmlBuilder::buildHtmlElement('p', ['id' => new HtmlBuilderTest()], new HtmlBuilderTest())
        );
    }
}