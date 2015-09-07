<?php

use Illuminate\Contracts\Support\Htmlable;

/**
 * Class HtmlContent is just a simple class to quickly input a Htmlable in tests.
 * The idea with Htmlables is otherwise that they would handle their own escaping etc, which this class doesn't
 */
class HtmlContent implements Htmlable
{
    protected $content = '';

    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Get content as a string of HTML
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->content;
    }
}