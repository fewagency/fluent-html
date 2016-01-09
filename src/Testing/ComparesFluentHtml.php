<?php
namespace FewAgency\FluentHtml\Testing;

use FewAgency\FluentHtml\FluentHtml;

trait ComparesFluentHtml
{
    use MakesHtmlComparable;

    /**
     * Helper assertion to check if html strings can be considered equal
     * @param $expected
     * @param FluentHtml $e
     * @param string|null $message
     */
    protected static function assertHtmlEquals($expected, FluentHtml $e, $message = null)
    {
        static::assertEquals(static::comparableHtml($expected), static::comparableHtml($e),
            $message ?: 'Not matching HTML string');
    }
}