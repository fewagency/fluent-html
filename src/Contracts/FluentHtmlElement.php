<?php
namespace FewAgency\FluentHtml\Contracts;

use FewAgency\FluentHtml\IdRegistrar;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;


/**
 * Fluent interface style HTML builder for building and displaying advanced elements structures.
 */
interface FluentHtmlElement extends Htmlable
{
    /**
     * Alias for withAppendedContent, to add html content last within this element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withContent($html_contents);

    /**
     * Add html content after existing content in the current element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withAppendedContent($html_contents);

    /**
     * Add html content before existing content in the current element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withPrependedContent($html_contents);

    /**
     * Add a raw string of html content last within this element.
     *
     * @param string $raw_html_content that will not be escaped
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withRawHtmlContent($raw_html_content);

    /**
     * Add html contents last within this element, with each new inserted content wrapped in an element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @param string|callable $wrapping_html_element_name
     * @param array|Arrayable $wrapping_tag_attributes
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withContentWrappedIn($html_contents, $wrapping_html_element_name, $wrapping_tag_attributes = []);

    /**
     * Add one or more named attributes with value to the current element.
     * Overrides any set attributes with same name.
     * Attributes evaluating to falsy will be unset.
     *
     * @param string|callable|array|Arrayable $attributes Attribute name as string, can also be an array of names and values, or a callable returning such an array.
     * @param string|bool|callable|array|Arrayable $value to set, only used if $attributes is a string
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withAttribute($attributes, $value = true);

    /**
     * Remove one or more named attributes from the current element.
     *
     * @param string|array|Arrayable $attributes,...
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withoutAttribute($attributes);

    /**
     * Set the id attribute on the current element.
     * Will check if the desired id is already taken and if so set another unique id.
     *
     * @param string $desired_id id that will be used if not already taken
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withId($desired_id);

    /**
     * Add one or more class names to the current element.
     *
     * @param string|callable|array|Arrayable $classes,...
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withClass($classes);

    /**
     * Remove one or more class names from the current element.
     *
     * @param string|array|Arrayable $classes,...
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withoutClass($classes);

    /**
     * Set the html element name.
     *
     * @param string|callable $html_element_name
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withHtmlElementName($html_element_name);

    /**
     * Will not display current element if any added condition evaluates to false.
     *
     * @param bool|callable $condition
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function onlyDisplayedIf($condition);

    /**
     * Will not display current element if it has no content
     *
     * @return \FewAgency\FluentHtml\FluentHtmlElement|FluentHtmlElement can be method-chained to modify the current element
     */
    public function onlyDisplayedIfHasContent();

    /**
     * Adds a new element last among this element's children and returns the new element.
     * Alias for endingWithElement()
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function containingElement($html_element_name = null, $tag_contents = [], $tag_attributes = []);

    /**
     * Adds a new element last among this element's children and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function endingWithElement($html_element_name, $tag_contents = [], $tag_attributes = []);

    /**
     * Adds a new element first among this element's children and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function startingWithElement($html_element_name, $tag_contents = [], $tag_attributes = []);

    /**
     * Adds a new element just after this element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function followedByElement($html_element_name, $tag_contents = [], $tag_attributes = []);

    /**
     * Adds a new element just before this element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function precededByElement($html_element_name, $tag_contents = [], $tag_attributes = []);

    /**
     * Wraps only this element in a new element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function wrappedInElement($html_element_name = null, $tag_attributes = []);

    /**
     * Wraps this element together with its siblings in a new element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function siblingsWrappedInElement($html_element_name, $tag_attributes = []);

    /**
     * Get or generate the closest parent for this element, even if it's unnamed.
     *
     * @return FluentHtmlElement existing parent object or a generated empty parent object
     */
    public function getParentElement();

    /**
     * Get the closest named parent element or an unnamed parent if none found.
     * This is the common parent of this element and its siblings as rendered in html.
     *
     * @return FluentHtmlElement representing the closest named parent or an unnamed parent if none found
     */
    public function getSiblingsCommonParent();

    /**
     * Get the element's id string if set, or generate a new id.
     *
     * @param string|null $desired_id optional id that will be used if not already taken
     * @return string a generated unique id (or a previously set id) for this element
     */
    public function getId($desired_id = null);

    /**
     * Find out if this element will have a specified class when rendered.
     *
     * @param string $class
     * @return bool
     */
    public function hasClass($class);

    /**
     * Get the evaluated value of a named attribute.
     *
     * @param string $attribute key to look for
     * @return string|bool|Collection|null The evaluated attribute set for the key
     */
    public function getAttribute($attribute);

    /**
     * Find out if this element will contain any content when rendered.
     *
     * @return bool true if this element has content to render after evaluation
     */
    public function hasContent();

    /**
     * Get the number of content pieces in this element.
     * Empty contents are counted too.
     *
     * @return int the number of separate pieces of content in this element
     */
    public function getContentCount();

    /**
     * Find out if this element is set to render.
     *
     * @return bool that is true only if all conditions for rendering this element evaluates to true
     */
    public function willRenderInHtml();

    /**
     * Get or set an IdRegistrar to use with this element tree.
     * If no parameter supplied this method will set the global HtmlIdRegistrar.
     * If an IdRegistrar has already been set or accessed once, that registrar will be returned.
     * Don't call this method until you really need it to keep the registrar unset as long as possible.
     *
     * @param null|IdRegistrar $id_registrar to set if not already set
     * @return IdRegistrar for this element's tree
     */
    public function idRegistrar(IdRegistrar $id_registrar = null);

    /**
     * Render element as html string.
     *
     * @return string containing rendered html of this element and all its descendants
     */
    public function toHtml();

    /**
     * Renders the full tree from top down as html, regardless of the position of the last element in the fluent chain of calls.
     *
     * @return string containing the full rendered html of the entire tree this element belongs to.
     */
    public function __toString();
}