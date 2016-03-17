<?php
use FewAgency\FluentHtml\FluentHtmlElement;
use FewAgency\FluentHtml\Testing\ComparesFluentHtml;
use FewAgency\FluentHtml\FluentHtml;

class FluentHtmlTest extends PHPUnit_Framework_TestCase
{
    use ComparesFluentHtml;

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

    public function testExceptionsInStringConversion()
    {
        $e = new FluentHtml('p', function () {
            throw new Exception('test exception');
        });

        $this->assertStringStartsWith('<!-- ', (string)$e);
        $this->assertStringEndsWith(' -->', (string)$e);
        $this->assertContains('test exception', (string)$e);
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

    public function testOnlyDisplayedIfString()
    {
        $e = new FluentHtml('br');
        $e->onlyDisplayedIf('abc');

        $this->assertHtmlEquals('<br>', $e);
    }

    public function testOnlyDisplayedIfNull()
    {
        $e = new FluentHtml('p');
        $e->onlyDisplayedIf(null);

        $this->assertHtmlEquals('', $e);
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

    public function testOnlyDisplayedIfCallableNull()
    {
        $e = new FluentHtml('p');
        $e->onlyDisplayedIf(function () {
            return null;
        });

        $this->assertHtmlEquals('', $e);
    }

    public function testOnlyDisplayedIfHasContent()
    {
        $e = new FluentHtml('p', function () {
            return '';
        });

        $this->assertHtmlEquals('<p></p>', $e);

        $e->onlyDisplayedIfHasContent();

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
            },
            'd' => true,
            'e' => false,
            ['f', 'g' => true, 'h' => false]
        ]);

        $this->assertHtmlEquals('<p> a b d f g </p>', $e);
    }

    public function testWithContentFluentHtml()
    {
        $e = new FluentHtml('p');
        $e2 = new FluentHtml('br');
        $e->withContent('text');
        $e->withContent($e2);
        $e->withContent('text');

        $this->assertHtmlEquals("<p> text <br> text </p>", $e);
        $this->assertSame($e, $e2->getParentElement(), "The inserted element doesn't reference its parent");
    }

    public function testWithContentFluentHtmlIsCloned()
    {
        $e = new FluentHtml('div');
        $e2 = new FluentHtml('p', 'abc');
        $e2->withId('a');
        $e->withContent($e2);
        $e->withContent($e2);
        $e2->withContent('123');

        $this->assertHtmlEquals('<div> <p id="a"> abc 123 </p> <p id="a2">abc</p> </div>', $e);
    }

    public function testWithRawHtmlContent()
    {
        $e = new FluentHtml('div');
        $e->withRawHtmlContent('<p>content</p>');

        $this->assertHtmlEquals('<div><p>content</p></div>', $e);
    }

    public function testNoElementName()
    {
        $e = new FluentHtml();
        $e->withContent('text');
        $e->withContent(new FluentHtml('br'));
        $e->withContent('text');

        $this->assertHtmlEquals("text <br> text", $e);
    }

    public function testContainingElement()
    {
        $e = new FluentHtml('div', 'text');

        $this->assertHtmlEquals(
            '<div> text <br class="a"> </div>',
            $e->containingElement('br')->withClass('a')
        );
    }

    public function testStartingWithElement()
    {
        $e = new FluentHtml('div', 'text');

        $this->assertHtmlEquals(
            '<div> <br class="a"> text </div>',
            $e->startingWithElement('br')->withClass('a')
        );
    }

    public function testFollowedByElement()
    {
        $e = FluentHtml::create('br')->withClass('a')->followedByElement('hr')->withClass('b');

        $this->assertHtmlEquals('<br class="a"> <hr class="b">', $e);
    }

    public function testPrecededByElement()
    {
        $e = FluentHtml::create('br')->withClass('a')->precededByElement('hr')->withClass('b');

        $this->assertHtmlEquals('<hr class="b"> <br class="a">', $e);
    }

    public function testWrappedInElement()
    {
        $br = FluentHtml::create('br');
        $p = $br->wrappedInElement('p');
        $br->withClass('a'); //This makes the test verify that the element has not been cloned when wrapped

        $this->assertHtmlEquals('<p><br class="a"></p>', $p);
    }

    public function testWrappedInElementDeep()
    {
        $e = FluentHtml::create('div')->containingElement('br')->wrappedInElement('p');

        $this->assertHtmlEquals('<div><p><br></p></div>', $e);
    }

    public function testSiblingsWrappedInElement()
    {
        $br = FluentHtml::create('br');
        $div = $br->followedByElement('hr')->siblingsWrappedInElement('div');
        $br->withClass('a'); //This makes the test verify that the element has not been cloned on insert

        $this->assertHtmlEquals('<div> <br class="a"> <hr> </div>', $div);
    }

    public function testSiblingsWrappedInElementDeep()
    {
        $e = FluentHtml::create('div')->withContent('text')->containingElement('br')->followedByElement('hr')->siblingsWrappedInElement('p');

        $this->assertHtmlEquals('<div> <p> text <br> <hr> </p> </div>', $e);
    }

    public function testGetAttribute()
    {
        $e = FluentHtml::create('p')->withAttribute('id', 'id1');

        $this->assertEquals('id1', $e->getAttribute('id'));
    }

    public function testGetAttributeClosure()
    {
        $e = FluentHtml::create('p')->withAttribute('id', function () {
            return 'id1';
        });

        $this->assertEquals('id1', $e->getAttribute('id'));
    }

    public function testHasClass()
    {
        $e = FluentHtml::create('p');

        $this->assertFalse($e->hasClass('classA'));

        $e->withClass([
            'classA',
            function () {
                return 'classB';
            },
            'classC' => true,
            'classD' => false,
        ]);

        $this->assertTrue($e->hasClass('classA'));
        $this->assertTrue($e->hasClass('classB'));
        $this->assertTrue($e->hasClass('classC'));
        $this->assertFalse($e->hasClass('classD'));
    }

    public function testGetIdPreSet()
    {
        $e = FluentHtml::create('br')->withAttribute('id', 'id1');

        $this->assertEquals('id1', $e->getId('id2'));
    }

    public function testGetIdDesired()
    {
        $e = FluentHtml::create('br');

        $this->assertEquals('id1', $e->getId('id1'));

        $e2 = FluentHtml::create('br');

        $this->assertNotEmpty($e2->getId('id1'));

        $this->assertNotEquals($e->getId(), $e2->getId());
    }

    public function testGetIdGenerated()
    {
        $e = FluentHtml::create('br');
        $e_id = $e->getId();

        $this->assertNotEmpty($e_id);
        $this->assertEquals($e_id, $e->getId());

        $e2 = FluentHtml::create('br');

        $this->assertNotEquals($e->getId(), $e2->getId());
    }

    public function testParentIdRegistrar()
    {
        $divA = FluentHtml::create('div');
        $brB = FluentHtml::create('br');
        $divA->withContent($brB);
        $id_registrar = new \FewAgency\FluentHtml\HtmlIdRegistrar();
        $divA->idRegistrar($id_registrar);
        $divA->withId('A');
        $brB->withId('A');

        $this->assertNotEquals($divA->getId(), $brB->getId());
        $this->assertSame($brB->idRegistrar(), $id_registrar);
    }

    public function testMultiIdRegistrar()
    {
        $divA = FluentHtml::create('div');
        $divA->idRegistrar(new \FewAgency\FluentHtml\HtmlIdRegistrar());
        $brB = FluentHtml::create('br');
        $brB->idRegistrar(new \FewAgency\FluentHtml\HtmlIdRegistrar());

        $this->assertEquals($divA->getId(), $brB->getId());

        $brA = $divA->containingElement('br');
        $divB = $brB->wrappedInElement('div');

        $this->assertEquals($brA->getId(), $divB->getId());
    }

    public function testInheritedIdRegistrar()
    {
        $divA = new \FewAgency\FluentHtml\Testing\FluentTestInheritorBaseElement('div');
        $id_registrar = new \FewAgency\FluentHtml\HtmlIdRegistrar();
        $divA->idRegistrar($id_registrar);

        $brB = $divA->createInstanceOf('FluentHtml', ['br']);

        $this->assertSame($brB->idRegistrar(), $id_registrar);
    }

    public function testHasContent()
    {
        $e = FluentHtml::create('p');

        $this->assertFalse($e->hasContent());

        $e->withContent('text');

        $this->assertTrue($e->hasContent());
    }

    public function testWithContentWrappedIn()
    {
        $e = FluentHtml::create('ul')->withContentWrappedIn(['1', '2'], 'li', ['class' => 'a']);

        $this->assertHtmlEquals('<ul> <li class="a">1</li> <li class="a">2</li> </ul>', $e);
    }

    public function testWithContentWrappedInEmptyArray()
    {
        $e = FluentHtml::create('ul')->withContentWrappedIn([], 'li', ['class' => 'a']);

        $this->assertHtmlEquals('<ul></ul>', $e);
    }

    public function testWithContentWrappedInNull()
    {
        $e = FluentHtml::create('ul')->withContentWrappedIn(null, 'li', ['class' => 'a']);

        $this->assertHtmlEquals('<ul></ul>', $e);
    }

    public function testGetContentCount()
    {
        $e = FluentHtml::create('p');

        $this->assertEquals(0, $e->getContentCount());

        $e->withContent('text 1');

        $this->assertEquals(1, $e->getContentCount());

        $content_element_A = FluentHtml::create('b', 'bold');
        $e->withContent($content_element_A);

        $this->assertEquals(2, $e->getContentCount());

        $content_element_A->followedByElement('i', 'italics');

        $this->assertEquals(3, $e->getContentCount());

        $content_element_A->withContent('not bold');

        $this->assertEquals(3, $e->getContentCount());

        //Test adding empty content
        $e->withContent(false);

        $this->assertEquals(3, $e->getContentCount());

        $e->withContent(null);

        $this->assertEquals(3, $e->getContentCount());

        $e->withContent('');

        $this->assertEquals(3, $e->getContentCount());
    }

    public function testGetAncestorInstanceOf()
    {
        $outer = FluentHtml::create('p');
        $inner = $outer->containingElement('br');

        $this->assertEquals($outer, $inner->getAncestorInstanceOf('FewAgency\FluentHtml\FluentHtml'));
        $this->assertNull($outer->getAncestorInstanceOf('FewAgency\FluentHtml\FluentHtml'));
    }

    public function testWithDefaultContent()
    {
        $e = FluentHtml::create('p')->onlyDisplayedIfHasContent();
        $e->withDefaultContent('A');

        $this->assertHtmlEquals('<p>A</p>', $e);

        $e->withContent('B');

        $this->assertHtmlEquals('<p>B</p>', $e);
    }

    public function testSetParent()
    {
        $e = FluentHtml::create('p')->afterInsertion(function (FluentHtmlElement $e) {
            if (!$e->hasContent()) {
                $e->withContent('A');
            }
        })->wrappedInElement('div');

        $this->assertHtmlEquals('<div><p>A</p></div>', $e);
    }

    public function testFollowedBy()
    {
        $e = FluentHtml::create('p');
        $e->followedByElement('p', 'D');
        $e->followedBy(FluentHtml::create('p', 'B'), FluentHtml::create('p', 'C'))->withContent('A');

        $this->assertHtmlEquals('<p>A</p> <p>B</p> <p>C</p> <p>D</p>', $e);
    }

    public function testPrecededBy()
    {
        $e = FluentHtml::create('p');
        $e->precededByElement('p', 'A');
        $e->precededBy(FluentHtml::create('p', 'B'), FluentHtml::create('p', 'C'))->withContent('D');

        $this->assertHtmlEquals('<p>A</p> <p>B</p> <p>C</p> <p>D</p>', $e);
    }

}