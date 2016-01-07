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
     */
    public function __construct($html_element_name = null, $tag_contents = [], $tag_attributes = [])
    {
        $this->html_attributes = new Collection();
        $this->html_contents = new Collection();
        $this->render_in_html = new Collection();

        $this->withHtmlElementName($html_element_name);
        $this->withContent($tag_contents);
        $this->withAttribute($tag_attributes);
    }

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