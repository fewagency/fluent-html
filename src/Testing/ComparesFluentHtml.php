<?php
namespace FewAgency\FluentHtml\Testing;

use FewAgency\FluentHtml\FluentHtmlElement;

trait ComparesFluentHtml
{
    use MakesHtmlComparable;

    /**
     * Helper assertion to check if FluentHtml html can be considered equal to expected string
     * @param string $expected
     * @param FluentHtmlElement $e
     * @param string|null $message
     */
    protected static function assertHtmlEquals($expected, FluentHtmlElement $e, $message = null)
    {
        static::assertEquals(static::comparableHtml($expected), static::comparableHtml($e),
            $message ?: 'FluentHtml not matching HTML string');
    }

    /**
     * Helper assertion to check if FluentHtml html content can be considered equal to expected string
     * @param string $expected
     * @param FluentHtmlElement $e
     * @param string|null $message
     */
    protected static function assertHtmlContentEquals($expected, FluentHtmlElement $e, $message = null)
    {
        $e->withHtmlElementName(null); //Removing the tag name makes the element hidden in html

        static::assertEquals(static::comparableHtml($expected), static::comparableHtml($e->branchToHtml()),
            $message ?: 'FluentHtml contents not matching HTML string');
    }
}