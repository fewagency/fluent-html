<?php
namespace FewAgency\FluentHtml\Testing;

trait MakesHtmlComparable
{
    /**
     * Makes an html string comparable.
     * @param $html_string
     * @return string
     */
    protected static function comparableHtml($html_string)
    {
        $html_string = self::whitespaceBetweenHtmlTags($html_string);
        $html_string = self::uniformHtmlWhitespace($html_string);

        return $html_string;
    }

    /**
     * Replaces any whitespace characters with a single space.
     * @param $html_string
     * @return mixed
     */
    protected static function uniformHtmlWhitespace($html_string)
    {
        return preg_replace('/\s+/', ' ', $html_string);
    }

    /**
     * Inserts whitespace between any adjacent html tags.
     * @param $html_string
     * @return mixed
     */
    protected static function whitespaceBetweenHtmlTags($html_string)
    {
        return preg_replace('/></', '> <', $html_string);
    }
}