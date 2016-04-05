<?php
namespace FewAgency\FluentHtml;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Implementation of Fluent interface style HTML builder for building and displaying advanced elements structures.
 */
class FluentHtml extends FluentHtmlElement
{
    /**
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     */
    public function __construct($html_element_name = null, $tag_contents = [], $tag_attributes = [])
    {
        parent::__construct();
        $this->withHtmlElementName($html_element_name);
        $this->withContent($tag_contents);
        $this->withAttribute($tag_attributes);
    }

    /**
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtml
     */
    public static function create($html_element_name = null, $tag_contents = [], $tag_attributes = [])
    {
        return new FluentHtml($html_element_name, $tag_contents, $tag_attributes);
    }
}