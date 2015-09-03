<?php namespace FewAgency\FluentHtml;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

/*
Examples how I'd like this to work:

element('input')->withAttribute('type', 'text')->withAttribute('value', 'Value')

element('p')->withContent('This is a string.', '...and this is another string')

element('div')->withContent(element('p','This is a paragraph'), element('p','Second p'))->withAttribute('class','divclass')

element('div')->containingElement('p','P1')->withClass('p1')->followedByElement('p','P2')

element('p','First p in div')->siblingsWrappedInElement('div')->withClass('divclass')->containingElement('p','Second p in div')

element('p','1st p in div')->followedByElement('p', '2nd p in div')->siblingsWrappedInElement('div')

element('p','paragraph')->followedByElement('strong','Strong text')->wrappedInElement('p')

element('div')->containingElement('p','Skipped...')->withClass('p1')->onlyDisplayedIf($falsy)->followedByElement('p','Displayed in div')->withClass('p2')

*/


class FluentHtml implements Htmlable
{
    /**
     * Quote character used around html attributes' values
     * @var string
     */
    protected $attribute_quote_char = '"';

    /**
     * This element's parent element, if any
     * @var FluentHtml
     */
    protected $parent;

    /**
     * If any item in the collection evaluates to false, this element and its children should be excluded from string
     * @var Collection
     */
    protected $render_in_html;

    /**
     * This element's html tag name, if any
     * @var string|callable
     */
    protected $html_element_name;

    /**
     * This element's attributes
     * @var Collection
     */
    protected $html_attributes;

    /**
     * This element's html content
     * @var Collection
     */
    protected $html_contents;

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

    /*
    |--------------------------------------------------------------------------
    | Methods modifying and returning same element
    |--------------------------------------------------------------------------
    |
    | These methods can be chained to modify the current element.
    |
    */

    /**
     * Alias for withAppendedContent, to add html content last within this element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withContent($html_contents)
    {
        return $this->withAppendedContent(func_get_args());
    }

    /**
     * Add html content after existing content.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withAppendedContent($html_contents)
    {
        $this->html_contents = $this->html_contents->merge($this->prepareContentsForInsertion(func_get_args()));

        return $this;
    }

    /**
     * Add html content before existing content.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withPrependedContent($html_contents)
    {
        $this->html_contents = $this->prepareContentsForInsertion(func_get_args())->merge($this->html_contents);

        return $this;
    }

    /**
     * Add named attributes to the current element.
     * Overrides any set attributes with same name.
     * Attributes evaluating to falsy will not be set.
     *
     * @param string|callable|array|Arrayable $attributes Attribute name as string, can also be an array of names and values, or a callable returning such an array.
     * @param string|bool|callable|array|Arrayable $value to set, only used if $attributes is a string
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withAttribute($attributes, $value = true)
    {
        if (is_string($attributes)) {
            $this->html_attributes->put($attributes, $value);
        } elseif (HtmlBuilder::useAsCallable($attributes)) {
            $this->html_attributes->push($attributes);
        } else {
            $this->html_attributes = $this->html_attributes->merge($attributes);
        }

        return $this;
    }

    /**
     * Remove named attributes from the current element.
     *
     * @param string|array|Arrayable $attributes,...
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withoutAttribute($attributes)
    {
        //We don't need to preserve keys here, so using standard Collection::flatten()
        Collection::make(func_get_args())->flatten()->each(function ($attribute) {
            $this->withAttribute($attribute, false);
        });

        return $this;
    }

    /**
     * Add class names to the current element.
     *
     * @param string|callable|array|Arrayable $classes,...
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withClass($classes)
    {
        return $this->withAttribute('class', $this->getClasses()->merge(HtmlBuilder::flatten(func_get_args())));
    }

    /**
     * Remove class names from the current element.
     *
     * @param string|array|Arrayable $classes,...
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withoutClass($classes)
    {
        return $this->withAttribute('class', $this->getClasses()->diff(Collection::make(func_get_args())->flatten()));
    }

    /**
     * Will not display current element if any added condition evaluates to false
     *
     * @param bool|callable $condition
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function onlyDisplayedIf($condition)
    {
        $this->render_in_html->push($condition);

        return $this;
    }

    /**
     * @param string|callable $html_element_name
     * @return $this|FluentHtml can be method-chained to modify the current element
     */
    public function withHtmlElementName($html_element_name)
    {
        $this->html_element_name = $html_element_name;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods creating and returning new element
    |--------------------------------------------------------------------------
    |
    | These methods creates and adds a new element relative to the current element.
    |
    */

    //Alias for endingWithElement()
    public function containingElement()
    {
        //TODO: implement containingElement
    }

    /**
     * Adds a new element last among this element's children
     *
     * @param string|callable $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtml
     */
    public function endingWithElement($html_element_name, $tag_contents = [], $tag_attributes = [])
    {
        //TODO: implement endingWithElement
    }

    //Adds a new element first among this element's children
    public function startingWithElement()
    {
        //TODO: implement startingWithElement
    }

    //Wraps this element together with its siblings in a new element
    public function siblingsWrappedInElement()
    {
        //TODO: implement siblingsWrappedInElement
    }

    //Wraps only this element in a new element
    public function wrappedInElement()
    {
        //TODO: implement wrappedInElement
    }

    //Adds a new element just after this element
    public function followedByElement()
    {
        //TODO: implement followedByElement
    }

    //Adds a new element just before this element
    public function precededByElement()
    {
        //TODO: implement precededByElement
    }

    /*
    |--------------------------------------------------------------------------
    | Methods returning existing element or a new empty element
    |--------------------------------------------------------------------------
    |
    | Used (mostly internally) to navigate between elements
    |
    */

    /**
     * @return FluentHtml
     */
    public function getParentElement()
    {
        return $this->parent ?: new FluentHtml(null, $this);
    }

    protected function getNextElement()
    {
    }

    protected function getPreviousElement()
    {
    }

    /**
     * Get the root element of this element's tree
     *
     * @return $this|FluentHtml
     */
    protected function getRootElement()
    {
        if ($this->parent) {
            return $this->parent->getRootElement();
        } else {
            return $this;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for finding out the state of this element
    |--------------------------------------------------------------------------
    */

    /**
     * @return bool that is true only if all conditions for rendering this element evaluates to true
     */
    public function renderInHtml()
    {
        return !$this->render_in_html->contains(function ($key, $value) {
            return !$this->evaluate($value);
        });
    }

    /**
     * @return Collection of classes currently set on this element
     */
    public function getClasses()
    {
        return Collection::make($this->html_attributes->get('class', []));
    }

    /*
    |--------------------------------------------------------------------------
    | Methods converting elements to strings etc
    |--------------------------------------------------------------------------
    |
    | When evaluated as a string, the whole html tree containing this element
    | will be returned.
    | This is useful for echoing in templates for example.
    |
    */

    /**
     * @return string containing rendered html of this element and all its descendants
     */
    public function toHtml()
    {
        if (!$this->renderInHtml()) {
            return '';
        }

        $html_contents = $this->evaluate($this->html_contents);
        $html_element_name = $this->evaluate($this->html_element_name);
        if ($html_element_name) {
            $html_attributes = $this->evaluate($this->html_attributes);

            return HtmlBuilder::buildHtmlElement($html_element_name, $html_attributes, $html_contents);
        } else {
            return HtmlBuilder::buildContentsString($html_contents);
        }
    }

    /**
     * Renders the full tree from top down, regardless of the position of the last element in the fluent chain of calls.
     *
     * @return string containing the full rendered html of the entire tree this element belongs to.
     */
    public function __toString()
    {
        return $this->getRootElement()->toHtml();
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for working with callables in element context
    |--------------------------------------------------------------------------
    |
    | Many methods take callables as parameters, these will usually be invoked
    | upon rendering to get the final values.
    |
    | The callables receives the current FluentHtml element as the first parameter.
    | Use this with caution!
    | Manipulating the FluentHtml object within the callable is not recommended,
    | use it for reading only!
    |
    */

    /**
     * Recursively evaluates input value if it's a callable, or returns the original value.
     * The current FluentHtml object is sent to each callable as the first parameter.
     *
     * @param mixed $value to evaluate, if it's a callback it will be invoked.
     * @return mixed Evaluated value, guaranteed not to be a callable.
     */
    protected function evaluate($value)
    {
        if (HtmlBuilder::useAsCallable($value)) {
            return $this->evaluate(call_user_func($value, $this));
        }
        if (HtmlBuilder::isArrayble($value)) {
            return Collection::make($value)->transform(function ($value) {
                return $this->evaluate($value);
            });
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for handling html content
    |--------------------------------------------------------------------------
    */

    /**
     * Takes a multidimensional array of contents and flattens it.
     * Also makes sure FluentHtml objects are cloned and have their parent set to the current object.
     *
     * @param string|Htmlable|FluentHtml|array|Arrayable $html_contents,...
     * @return Collection of contents that are ok to insert into a FluentHtml element
     */
    protected function prepareContentsForInsertion($html_contents)
    {
        return HtmlBuilder::flatten(func_get_args())->map(function ($item) {
            if ($item instanceof FluentHtml) {
                if ($item->parent) {
                    $item = clone $item;
                }
                $item->parent = $this;
            }
            return $item;
        });
    }

    /**
     * Cloning an object makes sure any html contents are cloned as well,
     * to keep the html a proper tree and never reference any object
     * from multiple places.
     */
    private function __clone()
    {
        $this->parent = null;
        $this->html_contents = $this->prepareContentsForInsertion($this->html_contents);
    }
}