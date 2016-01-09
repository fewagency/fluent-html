<?php
namespace FewAgency\FluentHtml\Testing;

trait MakesHtmlComparable
{
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