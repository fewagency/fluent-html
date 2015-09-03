<?php

use Illuminate\Contracts\Support\Htmlable;

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