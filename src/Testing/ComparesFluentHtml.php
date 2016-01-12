<?php
namespace FewAgency\FluentHtml\Testing;

use FewAgency\FluentHtml\FluentHtml;

trait ComparesFluentHtml
{
    use MakesHtmlComparable;

    /**
     * Helper assertion to check if FluentHtml html can be considered equal to expected string
     * @param $expected
     * @param FluentHtml $e
     * @param string|null $message
     */
    protected static function assertHtmlEquals($expected, FluentHtml $e, $message = null)
    {
        static::assertEquals(static::comparableHtml($expected), static::comparableHtml($e),
            $message ?: 'FluentHtml not matching HTML string');
    }

    /**
     * Helper assertion to check if FluentHtml html content can be considered equal to expected string
     * @param $expected
     * @param FluentHtml $e
     * @param string|null $message
     */
    protected static function assertHtmlContentEquals($expected, FluentHtml $e, $message = null)
    {
        $e->withHtmlElementName(null); //Removing the tag name makes the element hidden in html

        static::assertEquals(static::comparableHtml($expected), static::comparableHtml($e->toHtml()),
            $message ?: 'FluentHtml contents not matching HTML string');
    }
}