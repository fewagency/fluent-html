<?php
namespace FewAgency\FluentHtml;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class FluentHtml extends FluentHtmlElement
{
    /**
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return static
     */
    public static function create($html_element_name = null, $tag_contents = [], $tag_attributes = [])
    {
        return new static($html_element_name, $tag_contents, $tag_attributes);
    }

    /**
     * Create and return a new basic FluentHtmlElement instance
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement
     */
    protected function createFluentHtmlElement($html_element_name = null, $tag_contents = [], $tag_attributes = [])
    {
        return new self($html_element_name, $tag_contents, $tag_attributes);
    }
}