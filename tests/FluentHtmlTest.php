<?php

use FewAgency\FluentHtml\FluentHtml;

class FluentHtmlTest extends PHPUnit_Framework_TestCase
{

    /**
     * Helper assertion to check if html strings can be considered equal
     * @param $expected
     * @param FluentHtml $e
     * @param string|null $message
     */
    protected static function assertHtmlEquals($expected, FluentHtml $e, $message = null)
    {
        self::assertEquals(self::comparableHtml($expected), self::comparableHtml($e),
            $message ?: 'Not matching HTML string');
    }

    /**
     * Replaces any whitespace characters with a single space
     * @param $html_string
     * @return string
     */
    protected static function comparableHtml($html_string)
    {
        return preg_replace('/\s+/', ' ', $html_string);
    }


    public function testCanBeInstantiated()
    {
        $e = new FluentHtml();

        $this->assertInstanceOf('FewAgency\FluentHtml\FluentHtml', $e);
    }

    public function testCanBeConvertedToHtml()
    {
        $e = new FluentHtml();

        $this->assertEquals('', $e->toHtml());
    }

    public function testCanBeConvertedToString()
    {
        $e = new FluentHtml();

        $this->assertEquals('', (string)$e);
    }

    public function testCanMakeVoidElement()
    {
        $e = new FluentHtml('br');

        $this->assertHtmlEquals('<br>', $e);
    }

    public function testCanMakeNonVoidElement()
    {
        $e = new FluentHtml('p');

        $this->assertHtmlEquals('<p></p>', $e);
    }

    public function testOnlyDisplayedIfFalse()
    {
        $e = new FluentHtml('p');
        $e->onlyDisplayedIf(false);

        $this->assertHtmlEquals('', $e);
    }

    public function testOnlyDisplayedIfTrue()
    {
        $e = new FluentHtml('br');
        $e->onlyDisplayedIf(true);

        $this->assertHtmlEquals('<br>', $e);
    }

    public function testOnlyDisplayedIfCallableTrue()
    {
        $e = new FluentHtml('br');
        $e->onlyDisplayedIf(function () {
            return true;
        });

        $this->assertHtmlEquals('<br>', $e);
    }

    public function testOnlyDisplayedIfCallableFalse()
    {
        $e = new FluentHtml('p');
        $e->onlyDisplayedIf(function () {
            return false;
        });

        $this->assertHtmlEquals('', $e);
    }

    public function testWithAttribute()
    {
        $e = new FluentHtml('input');
        $e->withAttribute('type', 'text');

        $this->assertHtmlEquals('<input type="text">', $e);
    }

    public function testWithAttributeBoolean()
    {
        $e = new FluentHtml('input');
        $e->withAttribute('autofocus');

        $this->assertHtmlEquals('<input autofocus>', $e);
    }

    public function testWithAttributeMultiple()
    {
        $e = new FluentHtml('input');
        $e->withAttribute(['type' => 'text', 'name' => 'a']);

        $this->assertHtmlEquals('<input type="text" name="a">', $e);
    }

    public function testWithAttributeClosure()
    {
        $e = new FluentHtml('input');
        $e->withAttribute(function () {
            return ['type' => 'text', 'name' => 'a'];
        });

        $this->assertHtmlEquals('<input type="text" name="a">', $e);
    }

    public function testWithAttributeValueClosure()
    {
        $e = new FluentHtml('input');
        $e->withAttribute('type', function () {
            return 'text';
        });

        $this->assertHtmlEquals('<input type="text">', $e);
    }

    public function testWithAttributeMultilevel()
    {
        $e = new FluentHtml('input');
        $e->withAttribute([['type' => 'text'], ['name' => 'a']]);

        $this->assertHtmlEquals('<input type="text" name="a">', $e);
    }

    public function testWithoutAttribute()
    {
        $e = new FluentHtml('input');
        $e->withAttribute(['type' => 'text', 'name' => 'a']);

        $this->assertHtmlEquals('<input type="text" name="a">', $e);

        $e->withoutAttribute('type');

        $this->assertHtmlEquals('<input name="a">', $e);
    }

    public function testWithoutAttributeMultiple()
    {
        $e = new FluentHtml('input');
        $e->withAttribute(['type' => 'text', ['name' => 'a']]);

        $this->assertHtmlEquals('<input type="text" name="a">', $e);

        $e->withoutAttribute('type', 'name');

        $this->assertHtmlEquals('<input>', $e);
    }

    public function testWithClass()
    {
        $e = new FluentHtml('br');
        $e->withClass('a');
        $e->withClass('b');

        $this->assertHtmlEquals('<br class="a b">', $e);
    }

    public function testWithClassMultiple()
    {
        $e = new FluentHtml('br');
        $e->withClass([
            'a',
            'b' => function () {
                return true;
            },
            'c' => false,
            'd' => function () {
                return false;
            }
        ]);

        $this->assertHtmlEquals('<br class="a b">', $e);
    }

    public function testWithClassClosure()
    {
        $e = new FluentHtml('br');
        $e->withClass(function () {
            return function () {
                return [
                    'a',
                    function () {
                        return 'b';
                    }
                ];
            };
        });

        $this->assertHtmlEquals('<br class="a b">', $e);
    }

    public function testWithoutClass()
    {
        $e = new FluentHtml('br');
        $e->withClass(['a', 'b']);

        $this->assertHtmlEquals("<br class=\"a b\">", $e);

        $e->withoutClass('b');

        $this->assertHtmlEquals("<br class=\"a\">", $e);
    }

    public function testWithoutClassMultiple()
    {
        $e = new FluentHtml('br');
        $e->withClass([['a', 'b'], 'c']);

        $this->assertHtmlEquals("<br class=\"a b c\">", $e);

        $e->withoutClass('b', 'c');

        $this->assertHtmlEquals("<br class=\"a\">", $e);
    }

    public function testWithContent()
    {
        $e = new FluentHtml('p');
        $e->withContent('a');

        $this->assertHtmlEquals('<p>a</p>', $e);
    }

    public function testWithAppendedContent()
    {
        $e = new FluentHtml('p');
        $e->withContent('a')->withAppendedContent('b');

        $this->assertHtmlEquals("<p> a b </p>", $e);
    }

    public function testWithPrependedContent()
    {
        $e = new FluentHtml('p');
        $e->withContent('b')->withPrependedContent('a');

        $this->assertHtmlEquals("<p> a b </p>", $e);
    }

    public function testWithContentMultiple()
    {
        $e = new FluentHtml('p');
        $e->withContent([
            'a',
            'b' => function () {
                return true;
            },
            'c' => function () {
                return false;
            }
        ]);

        $this->assertHtmlEquals('<p> a b </p>', $e);
    }

    public function testWithContentFluidHtml()
    {
        $e = new FluentHtml('p');
        $e2 = new FluentHtml('br');
        $e->withContent('text');
        $e->withContent($e2);
        $e->withContent('text');

        $this->assertHtmlEquals("<p> text <br> text </p>", $e);
        $this->assertSame($e, $e2->getParentElement(), "The inserted element doesn't reference its parent");
    }

    public function testWithContentFluidHtmlIsCloned() {
        $e = new FluentHtml('div');
        $e2 = new FluentHtml('p','abc');
        $e->withContent($e2);
        $e->withContent($e2);
        $e2->withContent('123');

        $this->assertHtmlEquals("<div> <p> abc 123 </p> <p>abc</p> </div>", $e);
    }

    public function testNoElementName()
    {
        $e = new FluentHtml();
        $e->withContent('text');
        $e->withContent(new FluentHtml('br'));
        $e->withContent('text');

        $this->assertHtmlEquals("text <br> text", $e);
    }
}