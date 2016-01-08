<?php

use FewAgency\FluentHtml\FluentHtml;

abstract class FluentHtmlTestCase extends PHPUnit_Framework_TestCase
{

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

    /**
     * Replaces any whitespace characters with a single space
     * @param $html_string
     * @return string
     */
    protected static function comparableHtml($html_string)
    {
        return preg_replace('/\s+/', ' ', $html_string);
    }
}