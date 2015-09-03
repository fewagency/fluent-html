<?php namespace FewAgency\FluentHtml;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

class HtmlBuilder
{
    /**
     * Available attribute quote characters " and '
     */
    const ATTRIBUTE_QUOTE_CHARS = '"\'';

    /**
     * Constants to use for readability with the $escape_contents parameter
     */
    const ESCAPED = true;
    const UNESCAPED = false;

    /**
     * The html elements that have no closing element
     * @var array
     */
    public static $void_elements = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'menuitem',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    ];

    /**
     * @param string $tag_name
     * @param array|Arrayable $attributes
     * @param array|Arrayable|string|Htmlable $contents
     * @param bool $escape_contents defaults to true
     * @param string $attribute_quote_char to use for quotes around attribute values, can be either ' or default "
     * @return string
     */
    public static function buildHtmlElement(
        $tag_name,
        $attributes = [],
        $contents = [],
        $escape_contents = true,
        $attribute_quote_char = '"'
    ) {
        $tag_name = self::escapeHtml($tag_name);

        $tag_parts['opening'] = "<$tag_name";
        $tag_parts['opening'] .= self::buildAttributesString($attributes, $attribute_quote_char);
        $tag_parts['opening'] .= ">";

        $tag_parts['content'] = self::buildContentsString($contents, $escape_contents);

        if ($tag_parts['content'] or !in_array($tag_name, self::$void_elements)) {
            $tag_parts['closing'] = "</$tag_name>";
        }

        return implode(
            strlen(implode($tag_parts)) > 80 || str_contains($tag_parts['content'], "\n") ? "\n" : '',
            $tag_parts
        );
    }

    /**
     * Build a string of html contents
     *
     * @param string|Htmlable|array|Arrayable $contents
     * @return string
     */
    protected static function buildContentsString($contents, $escape_contents = true)
    {
        return self::flatten(self::evaluate($contents))->transform(function ($item, $key) use ($escape_contents) {
            if ($item instanceof Htmlable) {
                return $item->toHtml();
            }
            if (is_object($item)) {
                if (method_exists($item, '__toString')) {
                    $item = strval($item);
                } else {
                    return false;
                }
            }
            if (is_string($key) and trim($key) and $item) {
                $item = $key;
            }
            $item = trim($item);
            if ($escape_contents) {
                return self::escapeHtml($item);
            }

            return $item;
        })->filter(function ($item) {
            return !is_null($item) and !is_bool($item) and '' !== $item;
        })->implode("\n");
    }

    /**
     * Build an attribute string starting with space, to put after html tag name
     *
     * @param array|Arrayable $attributes
     * @param string $attribute_quote_char to use for quotes around attribute values, can be ' or default "
     * @return string
     */
    public static function buildAttributesString($attributes = [], $attribute_quote_char = '"')
    {
        $attribute_quote_char = self::getAttributeQuoteChar($attribute_quote_char);
        $attributes = self::flattenAttributes(self::evaluate($attributes));
        $attributes_string = '';
        foreach ($attributes as $attribute_name => $attribute_value) {
            if (is_object($attribute_value) and !method_exists($attribute_value, '__toString')) {
                $attribute_value = false;
            }
            if ($attribute_value !== false and !is_null($attribute_value)) {
                $attribute_value = self::flattenAttributeValue($attribute_name, $attribute_value);
                $attributes_string .= ' ' . self::escapeHtml($attribute_name);
                if ($attribute_value !== true) {
                    $attributes_string .= '=' . $attribute_quote_char . self::escapeHtml($attribute_value) . $attribute_quote_char;
                }
            }
        }

        return $attributes_string;
    }

    /**
     * Flatten out contents of any numeric attribute keys
     *
     * @param array|Collection|Arrayable $attributes
     * @return array
     */
    protected static function flattenAttributes($attributes)
    {
        $attributes = Collection::make($attributes);
        $flat_attributes = [];
        foreach ($attributes as $attribute_name => $attribute_value) {
            if (is_int($attribute_name)) {
                if (self::isArrayble($attribute_value)) {
                    $flat_attributes = array_merge($flat_attributes, self::flattenAttributes($attribute_value));
                } else {
                    $flat_attributes[$attribute_value] = true;
                }
            } else {
                $flat_attributes[$attribute_name] = $attribute_value;
            }
        }

        return $flat_attributes;
    }

    /**
     * If any attribute's value is an array, its contents will be flattened
     * into a comma or space separated string depending on type.
     *
     * @param string $attribute_name
     * @param mixed $attribute_value
     * @return string
     */
    protected static function flattenAttributeValue($attribute_name, $attribute_value)
    {
        if (self::isArrayble($attribute_value)) {
            //This attribute is a list of several values, check each value and build a string from them
            $attribute_value = self::flatten($attribute_value);
            $values = [];
            foreach ($attribute_value as $key => $value) {
                if ($value) {
                    if (is_int($key)) {
                        //If the key is numeric, the value is put in the list
                        $values[] = $value;
                    } else {
                        //If the key is a string, it's put in the list if the value is truthy
                        $values[] = $key;
                    }
                }
            }
            $attribute_value = implode($attribute_name == 'class' ? ' ' : ',', $values);
        }

        return $attribute_value;
    }

    /**
     * Make sure returned attribute quote character is valid for use
     *
     * @param $attribute_quote_char
     * @return string A valid attribute quote character
     */
    protected static function getAttributeQuoteChar($attribute_quote_char = '"')
    {
        if (!in_array($attribute_quote_char, str_split(self::ATTRIBUTE_QUOTE_CHARS))) {
            $attribute_quote_char = '"';
        }

        return $attribute_quote_char;
    }

    /**
     * Escape HTML characters
     *
     * @param Htmlable|string $value
     * @return string
     */
    public static function escapeHtml($value)
    {
        return e($value);
    }

    /**
     * Alias for escape()
     *
     * @param Htmlable|string $value
     * @return string
     */
    public static function e($value)
    {
        return self::escapeHtml($value);
    }

    /**
     * @param $value
     * @return bool
     */
    public static function isArrayble($value)
    {
        return is_array($value) or $value instanceof Arrayable;
    }

    /**
     * Determine if the given value is callable, but not a string nor a callable object-method array pair.
     *
     * @param  mixed $value
     * @return bool
     */
    public static function useAsCallable($value)
    {
        return !is_string($value) and !is_array($value) and is_callable($value);
    }

    /**
     * Recursively evaluates input value if it's a callable, or returns the original value.
     *
     * @param mixed $value to evaluate, any contained callbacks will be invoked.
     * @return mixed Evaluated value, guaranteed not to be a callable.
     */
    protected function evaluate($value)
    {
        if (self::useAsCallable($value)) {
            return self::evaluate(call_user_func($value));
        }
        if (self::isArrayble($value)) {
            return Collection::make($value)->transform(function ($value) {
                return self::evaluate($value);
            });
        }

        return $value;
    }

    /**
     * Flatten multidimensional arrays to a one level Collection, preserving keys.
     *
     * @param mixed $collection,...
     * @return Collection
     */
    public static function flatten($collection)
    {
        $flat_collection = Collection::make();
        $collection = func_get_args();
        array_walk_recursive($collection, function ($item, $key) use (&$flat_collection) {
            if ($item instanceof Arrayable) {
                $flat_collection = $flat_collection->merge(self::flatten($item->toArray()));
            } elseif (is_int($key)) {
                $flat_collection->push($item);
            } else {
                $flat_collection->put($key, $item);
            }
        });

        return $flat_collection;
    }
}