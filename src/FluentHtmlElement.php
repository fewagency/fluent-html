<?php namespace FewAgency\FluentHtml;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

// All usage of func_get_args() in this class can be replaced by ... on PHP >= 5.6 http://php.net/manual/en/functions.arguments.php#functions.variable-arg-list

/**
 * Abstract Fluent interface style HTML builder, use as a base to extend functionality for specific elements.
 */
abstract class FluentHtmlElement implements Htmlable
{
    /**
     * Quote character used around html attributes' values
     * @var string
     */
    protected $attribute_quote_char = '"';

    /**
     * This element's parent element, if any
     * @var FluentHtmlElement
     */
    private $parent;

    /**
     * If any item in the collection evaluates to false, this element and its children should be excluded from string
     * @var Collection
     */
    private $render_in_html;

    /**
     * This element's html tag name, if any
     * @var string|callable
     */
    private $html_element_name;

    /**
     * This element's attributes
     * @var Collection
     */
    private $html_attributes;

    /**
     * This element's html content
     * @var Collection
     */
    private $html_contents;

    /**
     * This element's default html content
     * @var Collection
     */
    private $default_html_contents;

    /**
     * This element tree's id registrar for keeping id's unique
     * Usually the root element's registrar is used
     * @var IdRegistrar
     */
    private $id_registrar;

    /**
     * Callbacks to run after element is inserted in tree
     * @var Collection
     */
    private $after_insertion_callbacks;

    /*
    |--------------------------------------------------------------------------
    | Methods creating new elements
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $this->clearAttributes();
        $this->clearContents();
        $this->render_in_html = new Collection();
        $this->after_insertion_callbacks = new Collection();
    }

    /**
     * Create and return a new basic FluentHtmlElement instance
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement
     */
    protected static function createFluentHtmlElement(
        $html_element_name = null,
        $tag_contents = [],
        $tag_attributes = []
    ) {
        return FluentHtml::create($html_element_name, $tag_contents, $tag_attributes);
    }

    /**
     * Create and return an instance of another FluentHtmlElement subclass.
     *
     * First look in each of the namespaces up through the class hierarchy,
     * then ask the parent FluentHtmlElement if set.
     *
     * @param string $classname
     * @param array $parameters
     * @return FluentHtmlElement
     */
    protected function createInstanceOf($classname, $parameters = [])
    {
        // Check for class relative the initially called class's namespace
        $class_refl = new \ReflectionClass(get_called_class());
        do {
            $class_ns = $class_refl->getNamespaceName();
            $namespaced_classname = $class_ns . '\\' . $classname;
            if (class_exists($namespaced_classname)) {
                // If found, create and return new instance with $parameters to constructor
                $class_refl = new \ReflectionClass($namespaced_classname);
                $instance = $class_refl->newInstanceArgs($parameters);
                if ($this->getRootElement()->hasIdRegistrar()) {
                    $instance->idRegistrar($this->getRootElement()->idRegistrar());
                }

                return $instance;
            }
            // try again in parent class' namespace if parent class exists
        } while ($class_refl = $class_refl->getParentClass());
        // Hand off to the parent FluentHtmlElement
        if ($this->hasParent()) {
            return $this->getParent()->createInstanceOf($classname, $parameters);
        }
        $message = "$classname could not be created by " . __METHOD__;
        throw new \InvalidArgumentException($message);
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
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withContent($html_contents)
    {
        return $this->withAppendedContent(func_get_args());
    }

    /**
     * Add html content after existing content in the current element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withAppendedContent($html_contents)
    {
        $this->html_contents = $this->html_contents->merge($this->prepareContentsForInsertion(func_get_args()));

        return $this;
    }

    /**
     * Add html content before existing content in the current element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withPrependedContent($html_contents)
    {
        $this->html_contents = $this->prepareContentsForInsertion(func_get_args())->merge($this->html_contents);

        return $this;
    }

    /**
     * Add html outside and after this element in the tree.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_siblings,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function followedBy($html_siblings)
    {
        //TODO: rename to withFollowingSibling()
        $this->getParentElement()->spliceContent($this->getParentElement()->getContentOffset($this) + 1,
            0, func_get_args());

        return $this;
    }

    /**
     * Add html outside and before this element in the tree.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_siblings,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function precededBy($html_siblings)
    {
        //TODO: rename to withPrecedingSibling()
        $this->getParentElement()->spliceContent($this->getParentElement()->getContentOffset($this),
            0, func_get_args());

        return $this;
    }

    /**
     * Wrap this element in another element at the same place in the tree.
     * @param FluentHtmlElement $wrapper
     * @return $this
     */
    protected function wrappedIn(FluentHtmlElement $wrapper)
    {
        if ($this->hasParent()) {
            $parent = $this->getParentElement();
            $this->setParent(null);
        }

        $wrapper->withAppendedContent($this);

        if (!empty($parent)) {
            $parent->html_contents->transform(function ($item) use ($wrapper, $parent) {
                if ($this === $item) {
                    $wrapper->setParent($parent);

                    return $wrapper;
                } else {
                    return $item;
                }
            });
        }

        return $this;
    }

    /**
     * Add a raw string of html content last within this element.
     *
     * @param string $raw_html_content that will not be escaped
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withRawHtmlContent($raw_html_content)
    {
        $html = new HtmlString($raw_html_content);

        return $this->withContent($html);
    }

    /**
     * Add html contents last within this element, with each new inserted content wrapped in an element.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @param string|callable $wrapping_html_element_name
     * @param array|Arrayable $wrapping_tag_attributes
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withContentWrappedIn($html_contents, $wrapping_html_element_name, $wrapping_tag_attributes = [])
    {
        HtmlBuilder::flatten($html_contents)->each(function ($html_content) use (
            $wrapping_html_element_name,
            $wrapping_tag_attributes
        ) {
            $this->withContent(
                static::createFluentHtmlElement($wrapping_html_element_name, $html_content, $wrapping_tag_attributes)
                    ->onlyDisplayedIfHasContent());
        });

        return $this;
    }

    /**
     * Set default html content to be used only if no other content is rendered.
     *
     * @param string|Htmlable|callable|array|Arrayable $html_contents,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withDefaultContent($html_contents)
    {
        $this->default_html_contents = $this->prepareContentsForInsertion(func_get_args());

        return $this;
    }

    /**
     * Clear all set contents.
     *
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    protected function clearContents()
    {
        $this->html_contents = new Collection();

        return $this;
    }

    /**
     * Clear all set attributes.
     *
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    protected function clearAttributes()
    {
        $this->html_attributes = new Collection();

        return $this;
    }

    /**
     * Add one or more named attributes with value to the current element.
     * Overrides any set attributes with same name.
     * Attributes evaluating to falsy will be unset.
     *
     * @param string|callable|array|Arrayable $attributes Attribute name as string, can also be an array of names and values, or a callable returning such an array.
     * @param string|bool|callable|array|Arrayable $value to set, only used if $attributes is a string
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
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
     * Remove one or more named attributes from the current element.
     *
     * @param string|array|Arrayable $attributes,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
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
     * Set the id attribute on the current element.
     * Will check if the desired id is already taken and if so set another unique id.
     *
     * @param string $desired_id id that will be used if not already taken
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withId($desired_id)
    {
        $this->withAttribute('id', $this->uniqueId($desired_id));

        return $this;
    }

    /**
     * Add one or more class names to the current element.
     *
     * @param string|callable|array|Arrayable $classes,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withClass($classes)
    {
        return $this->withAttribute('class', $this->getRawClasses()->merge(HtmlBuilder::flatten(func_get_args())));
    }

    /**
     * Remove one or more class names from the current element.
     *
     * @param string|array|Arrayable $classes,...
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withoutClass($classes)
    {
        return $this->withAttribute('class',
            $this->getRawClasses()->diff(Collection::make(func_get_args())->flatten()));
    }

    /**
     * Set the html element name.
     *
     * @param string|callable $html_element_name
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function withHtmlElementName($html_element_name)
    {
        $this->html_element_name = $html_element_name;

        return $this;
    }

    /**
     * Will not display current element if any added condition evaluates to false.
     *
     * @param bool|callable $condition
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function onlyDisplayedIf($condition)
    {
        if (is_null($condition)) {
            //Collection::contains() doesn't handle inverted null values very well, so we replace null with false
            $condition = false;
        }
        $this->render_in_html->push($condition);

        return $this;
    }

    /**
     * Will not display current element if it has no content
     *
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function onlyDisplayedIfHasContent()
    {
        $this->onlyDisplayedIf(function (FluentHtmlElement $current_object) {
            return $current_object->hasContent();
        });

        return $this;
    }

    /**
     * Register callbacks to run after element is placed in an element tree.
     * The inserted element will be supplied as the first argument to the closure.
     * It's usually a good idea to check for some condition on the element before manipulating it within the closure,
     * because an element may be inserted into other elements many times throughout it's lifetime.
     * @param callable $callback
     * @return $this|FluentHtmlElement can be method-chained to modify the current element
     */
    public function afterInsertion($callback)
    {
        $this->after_insertion_callbacks->push($callback);

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

    /**
     * Adds a new element last among this element's children and returns the new element.
     * Alias for endingWithElement()
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function containingElement($html_element_name = null, $tag_contents = [], $tag_attributes = [])
    {
        return $this->endingWithElement($html_element_name, $tag_contents, $tag_attributes);
    }

    /**
     * Adds a new element last among this element's children and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function endingWithElement($html_element_name, $tag_contents = [], $tag_attributes = [])
    {
        $e = static::createFluentHtmlElement($html_element_name, $tag_contents, $tag_attributes);
        $this->withContent($e);

        return $e;
    }

    /**
     * Adds a new element first among this element's children and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function startingWithElement($html_element_name, $tag_contents = [], $tag_attributes = [])
    {
        $e = static::createFluentHtmlElement($html_element_name, $tag_contents, $tag_attributes);
        $this->withPrependedContent($e);

        return $e;
    }

    /**
     * Adds a new element outside and just after this element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function followedByElement($html_element_name, $tag_contents = [], $tag_attributes = [])
    {
        $e = static::createFluentHtmlElement($html_element_name, $tag_contents, $tag_attributes);
        $this->followedBy($e);

        return $e;
    }

    /**
     * Adds a new element just before this element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param string|Htmlable|array|Arrayable $tag_contents
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function precededByElement($html_element_name, $tag_contents = [], $tag_attributes = [])
    {
        $e = static::createFluentHtmlElement($html_element_name, $tag_contents, $tag_attributes);
        $this->precededBy($e);

        return $e;
    }

    /**
     * Wraps only this element in a new element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function wrappedInElement($html_element_name = null, $tag_attributes = [])
    {
        $wrapper = static::createFluentHtmlElement($html_element_name, null, $tag_attributes);
        $this->wrappedIn($wrapper);

        return $wrapper;
    }

    /**
     * Wraps this element together with its siblings in a new element and returns the new element.
     *
     * @param string|callable|null $html_element_name
     * @param array|Arrayable $tag_attributes
     * @return FluentHtmlElement representing the new element
     */
    public function siblingsWrappedInElement($html_element_name, $tag_attributes = [])
    {
        $parent = $this->getSiblingsCommonParent();
        $siblings = $parent->html_contents->each(function ($item) {
            if ($item instanceof FluentHtmlElement) {
                $item->setParent(null);
            }
        });
        $wrapper = static::createFluentHtmlElement($html_element_name, $siblings, $tag_attributes);

        $parent->clearContents()->withContent($wrapper);

        return $wrapper;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods returning an existing element in the tree
    |--------------------------------------------------------------------------
    |
    | Used (mostly internally) to navigate between elements.
    |
    | May sometimes return a new empty element if the desired one doesn't yet exist.
    |
    */

    /**
     * Get or generate the closest parent for this element, even if it's unnamed.
     *
     * @return FluentHtmlElement existing parent object or a generated empty parent object
     */
    public function getParentElement()
    {
        return $this->getParent() ?: static::createFluentHtmlElement(null, $this);
    }

    /**
     * Get the closest named parent element or an unnamed parent if none found.
     * This is the common parent of this element and its siblings as rendered in html.
     *
     * @return FluentHtmlElement representing the closest named parent or an unnamed parent if none found
     */
    public function getSiblingsCommonParent()
    {
        if ($this->hasParent()) {
            if ($this->getParent()->hasHtmlElementName()) {
                return $this->getParent();
            } else {
                return $this->getParent()->getSiblingsCommonParent();
            }
        } else {
            return $this->getParentElement();
        }
    }

    /**
     * Get the root element of this element's tree.
     *
     * @return FluentHtmlElement
     */
    protected function getRootElement()
    {
        if ($this->isRootElement()) {
            return $this;
        } else {
            return $this->getParent()->getRootElement();
        }
    }

    /**
     * Get the closest ancestor matching class type
     *
     * @param string $type class or interface name to find
     * @return FluentHtmlElement|null
     */
    public function getAncestorInstanceOf($type)
    {
        if (!$this->hasParent()) {
            return null;
        }

        return ($this->getParent() instanceof $type) ? $this->getParent() : $this->getParent()->getAncestorInstanceOf($type);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for finding out the state of this element
    |--------------------------------------------------------------------------
    */

    /**
     * Get the element's id string if set, or generate a new id.
     *
     * @param string|null $desired_id optional id that will be used if not already taken
     * @return string a generated unique id (or a previously set id) for this element
     */
    public function getId($desired_id = null)
    {
        if (!$this->getAttribute('id')) {
            if (empty($desired_id)) {
                $desired_id = $this->getDefaultId();
            }
            $this->withId($desired_id);
        }

        return $this->getAttribute('id');
    }

    /**
     * Find out if this element will have a specified class when rendered.
     *
     * @param string $class
     * @return bool
     */
    public function hasClass($class)
    {
        if ($classes = $this->getAttribute('class')) {
            return in_array($class, explode(' ', HtmlBuilder::flattenAttributeValue('class', $classes)));
        }

        return false;
    }

    /**
     * Get collection of raw classes.
     *
     * @return Collection of classes currently set on this element
     */
    protected function getRawClasses()
    {
        return Collection::make($this->html_attributes->get('class', []));
    }

    /**
     * Get the evaluated value of a named attribute.
     *
     * @param string $attribute key to look for
     * @return string|bool|Collection|null The evaluated attribute set for the key
     */
    public function getAttribute($attribute)
    {
        return $this->evaluate($this->html_attributes->get($attribute));
    }

    /**
     * Get the raw value of a named attribute.
     *
     * @param string $attribute key to look for
     * @return string|Collection The raw attribute set for the key (not evaluated)
     */
    protected function getRawAttribute($attribute)
    {
        return $this->html_attributes->get($attribute);
    }

    /**
     * Find out if this element will contain any content when rendered.
     *
     * @return bool true if this element has content to render after evaluation
     */
    public function hasContent()
    {
        $html_contents = $this->evaluate($this->html_contents);
        $default_html_contents = $this->evaluate($this->default_html_contents);

        return (bool)(HtmlBuilder::buildContentsString($html_contents) or HtmlBuilder::buildContentsString($default_html_contents));
    }

    /**
     * Get the number of content pieces in this element.
     * Empty contents are counted too.
     *
     * @return int the number of separate pieces of content in this element
     */
    public function getContentCount()
    {
        return count($this->html_contents);
    }

    /**
     * Find out if this element is set to render.
     *
     * @return bool that is true only if all conditions for rendering this element evaluates to true
     */
    public function willRenderInHtml()
    {
        return !$this->render_in_html->contains(function ($key, $value) {
            return !$this->evaluate($value);
        });
    }

    /**
     * Find out if this element is the root of the element tree.
     *
     * @return bool true if this element is the root element of its tree
     */
    protected function isRootElement()
    {
        return !$this->hasParent();
    }

    /**
     * Get the offset of a specified piece of content within this element (internal).
     *
     * @param FluentHtmlElement|string|mixed $content to look for
     * @return mixed key for matching content, or false if not found
     */
    protected function getContentOffset($content)
    {
        return $this->html_contents->search($content);
    }

    /**
     * Find out if this element has a parent element.
     *
     * @return bool true if this element has a parent element
     */
    protected function hasParent()
    {
        return (bool)$this->getParent();
    }

    /**
     * Find out if this element has an element name set.
     *
     * @return bool true if the element name has been set (even if it's a callable that returns nothing)
     */
    protected function hasHtmlElementName()
    {
        return !empty($this->html_element_name);
    }

    /**
     * Get the evaluated element name.
     *
     * @return string|null
     */
    protected function getHtmlElementName()
    {
        return $this->evaluate($this->html_element_name);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for handling IdRegistrar
    |--------------------------------------------------------------------------
    */

    /**
     * Internal method to get the default id to try when no desired id has been supplied.
     * This is a good method to override in subclasses to set another default id.
     * The default id may be built from other element properties, e.g. id's from ancestors.
     * If the default id is a fairly static string, expected to be used multiple times,
     * adding a 1 to the end of the returned string will make the sequence look better.
     *
     * @return string
     */
    protected function getDefaultId()
    {
        $desired_id = class_basename($this) . '1';

        return $desired_id;
    }

    /**
     * Internal method to register a new unique id with the IdRegistrar.
     * See withId() and getId() for daily usage.
     *
     * @param string $desired_id to check if taken
     * @return string id to use, guaranteed to be unique in this registrar
     */
    protected function uniqueId($desired_id)
    {
        return $this->idRegistrar()->unique($desired_id);
    }

    /**
     * Get or set an IdRegistrar to use with this element tree.
     * If no parameter supplied this method will set the global HtmlIdRegistrar.
     * If an IdRegistrar has already been set or accessed once, that registrar will be returned.
     * Don't call this method until you really need it to keep the registrar unset as long as possible.
     *
     * @param null|IdRegistrar $id_registrar to set if not already set
     * @return IdRegistrar for this element's tree
     */
    public function idRegistrar(IdRegistrar $id_registrar = null)
    {
        if ($this->isRootElement()) {
            if (!$this->hasIdRegistrar()) {
                $this->id_registrar = $id_registrar ?: HtmlIdRegistrar::getGlobalInstance();
            }

            return $this->id_registrar;
        }

        return $this->getRootElement()->idRegistrar($id_registrar);
    }

    /**
     * Find out if this element has an IdRegistrar
     *
     * @return bool true if IdRegistrar is set explicitly on this element
     */
    public function hasIdRegistrar()
    {
        return !empty($this->id_registrar);
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
     * Render element as html string.
     *
     * @return string containing rendered html of this element and all its descendants
     */
    public function toHtml()
    {
        try {
            if (!$this->willRenderInHtml()) {
                return '';
            }

            $html_contents = $this->evaluate($this->html_contents);
            if (empty($html_contents) or ($html_contents instanceof Collection and $html_contents->isEmpty())) {
                $html_contents = $this->evaluate($this->default_html_contents);
            }
            //Set this as parent on any content FluentHtmlElement that doesn't already have a parent
            $html_contents = HtmlBuilder::flatten($html_contents)->map(function ($item) {
                if ($item instanceof FluentHtmlElement and !$item->hasParent()) {
                    $item->setParent($this);
                }

                return $item;
            });
            $html_element_name = $this->getHtmlElementName();
            if ($html_element_name) {
                $html_attributes = $this->evaluate($this->html_attributes);

                return HtmlBuilder::buildHtmlElement($html_element_name, $html_attributes, $html_contents);
            } else {
                return HtmlBuilder::buildContentsString($html_contents);
            }
        } catch (\Exception $e) {
            return '<!-- ' . get_class($e) . ' in ' . __METHOD__ . ': ' . $e->getMessage() . ' -->';
        }
    }

    /**
     * Render the full tree from top down as html, regardless of the position of the last element in the fluent chain of calls.
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
    | The callables receives the current FluentHtmlElement as the first parameter.
    | Use this with caution!
    | Manipulating the FluentHtmlElement object within the callable is not recommended,
    | use it for reading only!
    |
    */

    /**
     * Recursively evaluate input value if it's a callable, or returns the original value.
     * The current FluentHtmlElement object is sent to each callable as the first parameter.
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
            $collection = $value instanceof Collection ? $value->make($value) : new Collection($value);

            return $collection->transform(function ($value) {
                return $this->evaluate($value);
            });
        }

        return $value;
    }

    /**
     * Return debug data for this object
     *
     * @return mixed
     */
    public function __debugInfo()
    {
        $info['OBJECT#'] = spl_object_hash($this);
        $html_element_name = $this->getHtmlElementName();
        if ($html_element_name) {
            $info['tag'] = $html_element_name;
            $html_attributes = $this->evaluate($this->html_attributes);
            if ($html_attributes->count()) {
                $info['attributes'] = $html_attributes->toArray();
            }
        }
        if ($this->hasParent()) {
            $info['parent']['tag'] = $this->getParent()->getHtmlElementName();
            $info['parent']['OBJECT#'] = spl_object_hash($this->getParent());
            //$info['parent'] = $this->parent->__debugInfo();
        }
        foreach ($this->html_contents as $content) {
            if ($content instanceof FluentHtmlElement) {
                $content_html_element_name = (string)$content->getHtmlElementName();
                if (!isset($info['contents'][$content_html_element_name])) {
                    $info['contents'][$content_html_element_name] = 0;
                }
                $info['contents'][$content_html_element_name]++;
            } else {
                $info['contents'][] = $content;
            }
        }

        return $info;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for handling html content
    |--------------------------------------------------------------------------
    */

    /**
     * Take a multidimensional array of contents and flattens it.
     * Also make sure FluentHtmlElement objects are cloned and have their parent set to the current object.
     *
     * @param string|Htmlable|FluentHtmlElement|array|Arrayable $html_contents,...
     * @return Collection of contents that are ok to insert into a FluentHtmlElement element
     */
    protected function prepareContentsForInsertion($html_contents)
    {
        return HtmlBuilder::flatten(func_get_args())->map(function ($item) {
            if ($item instanceof FluentHtmlElement) {
                $original_id = $item->getAttribute('id');
                if ($item->hasParent()) {
                    $item = clone $item;
                }
                $item->setParent($this);
                if ($item->getAttribute('id') != $original_id) {
                    $item->withId($original_id);
                }
            }

            return $item;
        })->filter(function ($item) {
            //Filter out empty strings and false values
            return isset($item) and '' !== $item and false !== $item;
        });
    }

    /**
     * Splice a portion of the underlying content.
     *
     * @param  int $offset
     * @param  int|null $length
     * @param  mixed $replacement
     * @return Collection
     */
    protected function spliceContent($offset, $length = null, $replacement = [])
    {
        $replacement = $this->prepareContentsForInsertion($replacement);

        return $this->html_contents->splice($offset, $length, $replacement);
    }

    /**
     * Cloning an object makes sure any html contents are cloned as well,
     * to keep the html a proper tree and never reference any object
     * from multiple places.
     */
    public function __clone()
    {
        $this->html_attributes = clone $this->html_attributes;
        $this->render_in_html = clone $this->render_in_html;
        $this->after_insertion_callbacks = clone $this->after_insertion_callbacks;
        $this->html_contents = $this->prepareContentsForInsertion($this->html_contents);
        $this->setParent(null);
        $this->withoutAttribute('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods for handling internals
    |--------------------------------------------------------------------------
    */

    /**
     * Get this element's parent element.
     * For internal access (without creating a blank parent), see getParentElement() for other use!
     *
     * @return FluentHtmlElement|null
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * Set this element's parent element.
     *
     * @param FluentHtmlElement|null $parent
     */
    protected function setParent(FluentHtmlElement $parent = null)
    {
        if (!empty($parent) and $this->hasIdRegistrar()) {
            // Move inserted element's IdRegistrar upwards in the tree if element has one and the tree doesn't
            $parent->idRegistrar($this->idRegistrar());
        }
        $this->parent = $parent;
        if (!empty($parent)) {
            foreach ($this->after_insertion_callbacks as $callback) {
                call_user_func($callback, $this);
            }
        }
    }
}