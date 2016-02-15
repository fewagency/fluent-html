<?php
namespace FewAgency\FluentHtml;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;

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

    /**
     * Create and return an instance of another FluentHtml subclass.
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

                return $class_refl->newInstanceArgs($parameters);
            }
            // try parent class namespace if parent class exists
        } while ($class_refl = $class_refl->getParentClass());
        // If not found in parent classes, hand off to the parent FluentHtmlElement
        if ($this->hasParent()) {
            return $this->getParent()->createInstanceOf($classname, $parameters);
        }
    }

}