# [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) HTML builder for PHP
This package can be used on its own for building complex HTML structures,
but most of its power comes when extended for specific purposes.

For example, [fewagency/fluent-form](https://github.com/fewagency/fluent-form),
one of our other packages, helps you create accessible, well-formated, yet customizable HTML forms
extending [`FluentHtmlElement`](src/FluentHtmlElement.php). 

* [Introduction](#introduction)
    - [What's the point?](#point)
    - [Advanced Bootstrap example](#example-bootstrap)
    - [When to use (and not)](#when-to-use-and-not)
    - [Naming principles](#naming-principles)
* [Installation](#installation--configuration)
* [Usage](#usage)
    - [Collections as input](#collections-as-method-input)
        * [Conditional output](#conditional-output)
    - [Closures as input](#closures-as-method-input)
    - [Multiple attribute values](#multiple-attribute-values)
    - [Blade templates](#usage-with-blade-templates)
* [Methods reference](#methods-reference)
* [Development](#development)
* [Alternatives](#alternatives)
* [Authors - FEW Agency](#authors)
* [License](#license)

## Introduction 

```php
// Simple example
echo FluentHtml::create('div')->withClass('wrapper')
    ->containingElement('p')->withAttribute('title', 'p1')
    ->withContent(
        'This is a paragraph.',
        'It has two sentences.')
    ->followedByElement('p')->withAttribute('title', 'p2')
    ->withContent(
        'This is another paragraph.'
    );
```

Woha, that's a very elaborate way of writing:

```html
<div class="wrapper">
<p title="p1">
This is a paragraph.
It has two sentences.
</p>
<p title="p2">This is another paragraph.</p>
</div>
```

<a id="point"></a>
__So, then what's the point of it all?__
The power of `FluentHtml` comes from the ability to add collections of values, closures and conditions to the html
building process.
When the complexity grows you can build elements step by step and and trust the end result to be correct and
well-formatted HTML in every situation.

<a id="example-bootstrap"></a>
For example when generating [Bootstrap form-groups](http://getbootstrap.com/css/#forms) for an input or
[input-group](http://getbootstrap.com/components/#input-groups) with label,
[validation states](http://getbootstrap.com/css/#forms-control-validation), and
[help-text](http://getbootstrap.com/css/#forms-help-text)
...the desired HTML would look like this:

```html
<div class="form-group has-error">
<label class="control-label" for="FluentHtml1">username</label>
<div class="input-group">
<span class="input-group-addon"><input type="checkbox" aria-label="Addon checkbox"></span>
<input type="text" class="form-control" name="username" value="test@test.com" readonly aria-describedby="FluentHtml2" id="FluentHtml1">
<span class="input-group-btn"><button class="btn btn-default" type="button">Go!</button></span>
</div>
<div id="FluentHtml2">
<ul class="help-block list-unstyled">
<li class="text-capitalize-first">username is required</li>
<li class="text-capitalize-first">username must be a valid email address</li>
</ul>
<span class="help-block">username is your email address</span>
</div>
</div>
```

Generating the above in a PHP template could be a hassle.
With if-statements repeated all over the place, it would be very prone to errors sneaking in.
Using `FluentHtml` the code would probably take about the same space,
but it would be a lot more readable,
guaranteed to print correct and well-formatted HTML,
and can be split in manageable and reusable chunks,
like this:

```php
// Bootstrap form-group example

// Some basic options
$name = 'username';
$value = 'test@test.com';
// If there are errors on the input, show it, if not don't even print the elements
$errors[$name] = ["{$name} is required", "{$name} must be a valid email address"];
// If a help text is set, print it with aria-describedby
$help_text = "{$name} is your email address";
// We declare some optional input addons that makes the input wrapped in an input-group if set
$input_group_prepend = new \Illuminate\Support\HtmlString(
    '<span class="input-group-addon"><input type="checkbox" aria-label="Addon checkbox"></span>'
);
$input_group_append = new \Illuminate\Support\HtmlString(
    '<span class="input-group-btn"><button class="btn btn-default" type="button">Go!</button></span>'
);

// Build the input's help (aria-describedby) element and keep a reference
$control_help = FluentHtml::create('div')->onlyDisplayedIfHasContent();

// Add any errors relevant to the input as a list in the help element
$control_help->containingElement('ul')->withClass('help-block', 'list-unstyled')
    ->onlyDisplayedIfHasContent()
    // Wrap each error message in a list item element
    ->withContentWrappedIn($errors[$name], 'li', ['class' => 'text-capitalize-first'])
    // Put the fixed message at the end of the help element
    ->followedByElement('span', $help_text)->withClass('help-block')
    ->onlyDisplayedIfHasContent();

// Build the input element and keep a reference
$input = FluentHtml::create('input')->withAttribute('type', 'text')
    ->withClass('form-control')
    ->withAttribute(['name' => $name, 'value' => $value, 'readonly'])
    ->withAttribute('aria-describedby', function () use ($control_help) {
        // Only set the input's aria-describedby attribute if the help element has any content
        if ($control_help->hasContent()) {
            return $control_help->getId();
        }
    });

// Build the input-group
$input_group = $input->siblingsWrappedInElement(function ($input_group) {
    // Print the input-group tag only when there's at least one input group addon next to the input
    return $input_group->getContentCount() > 1 ? 'div' : false;
})->withClass('input-group')
    //Add the input group addons if they are set
    ->withPrependedContent($input_group_prepend)->withAppendedContent($input_group_append);

// Wrap up and print the full result from here
echo $input_group
    // Add a label before the input-group, defaulting to the input name if label not specified
    ->precededByElement('label', empty($label) ? $name : $label)->withClass('control-label')
    ->withAttribute('for', function () use ($input) {
        return $input->getId();
    })
    // Wrap the label and input-group in a form-group
    ->siblingsWrappedInElement('div')->withClass('form-group')
    ->withClass(function () use ($errors, $name) {
        // Set the validation state class on the form-group
        if (count($errors[$name])) {
            return 'has-error';
        }
    })
    // Add the help element last in the form-group
    ->withAppendedContent($control_help);
```

### When to use (and not)
`FluentHtml` should be used for those cases where you build
complex html structures with many if-statements.
Stay with your standard html views or templates for all the simple stuff!

If you're making something that should be reusable, consider creating a package of Elements extending
[`FluentHtmlElement`](src/FluentHtmlElement.php) and publish it!

### Naming principles
Public methods available on implementations of [`FluentHtmlElement`](src/FluentHtmlElement.php) (of which `FluentHtml` is one)
should be named to hint their return types.

Method names starting with `with...` should always return the current element for fluent chaining.
Like `withContent()` and `withAttribute()`, and in this category we also find `withoutAttribute()`.

Methods **adding conditions** to an element may have names containing
`If` or `Unless`, like `onlyDisplayedIfHasContent()`.
Methods adding conditions should always return the current element for fluid chaining.

Methods **adding callbacks** that will be triggered at certain events start with `on...`, `before...`, or `after...`.
`afterInsertion()` is the only event currently supported.
Methods adding callbacks should always return the current element for fluid chaining.

Methods that create, insert and return new `FluentHtml` instances relative to the current element ends with `...Element`.
Like `containingElement()`, `precededByElement()`, and `wrappedInElement()`.
Extending packages may add similarly named methods that return specific types of elements,
see [fewagency/fluent-form](https://github.com/fewagency/fluent-form) for some examples.

Methods starting with `get...` of course returns values of the expected type,
like `getParentElement()` returning an implementation of `FluentHtmlElement` and `getId()` returning a string.

Methods returning **booleans** start with `is...`, `has...`, or `will...`

## Installation & configuration
> composer require fewagency/fluent-html

### Optional facades
You may add [Laravel facades](http://laravel.com/docs/facades) in the `aliases` array of your project's
`config/app.php` configuration file:

```php
'FluentHtml'  => FewAgency\FluentHtml\Facades\FluentHtml::class,
'HtmlBuilder' => FewAgency\FluentHtml\Facades\HtmlBuilder::class,
```

### Dependencies
This package takes advantage of the [`Collection`](https://github.com/illuminate/support/blob/master/Collection.php)
implementation ([docs](http://laravel.com/docs/collections)) and the
[`Arrayable`](https://github.com/illuminate/contracts/blob/master/Support/Arrayable.php) and
[`Htmlable`](https://github.com/illuminate/contracts/blob/master/Support/Htmlable.php) interfaces from
[Laravel](http://laravel.com/docs)'s [Illuminate](https://github.com/illuminate) components.

Internally `FluentHtmlElement` depends on [`HtmlBuilder`](src/HtmlBuilder.php) to render html elements as strings
and [`HtmlIdRegistrar`](src/HtmlIdRegistrar.php) to keep track of used element ids so they can be kept unique.   

## Usage

### Collections as method input
Most methods accept arrays or collections (and other implementations of
[`Arrayable`](https://github.com/illuminate/contracts/blob/master/Support/Arrayable.php))
as input parameters.
A value may sometimes also be a nested collection, in which case the whole collection is recursively flattened
(with preserved associative keys).
When flattening a collection, any duplicate associative keys will be merged over by those appearing later in the
collection.
Values with numeric keys are always appended.

```php
// Example with collections and conditions
echo FluentHtml::create('input')->withAttribute([
    'name' => 'a',
    'disabled' => true,
    ['name' => 'b', 'disabled' => false, 'value' => 'B'],
    ['disabled' => true],
    'autofocus'
]);
```

```html
<input name="b" disabled value="B" autofocus>
```

#### Conditional output
String keys are usually displayed instead of their value if their corresponding evaluated value is truthy.
This makes it possible to conditionally show or hide html contents and element attributes, depending on their value
being true or false.

### Closures as method input
Most values can be [PHP closures](http://php.net/manual/en/functions.anonymous.php) in which case their evaluation is
deferred as long as possible, usually until the object is rendered as a string.
When a closure is evaluated it may return a value, boolean, arrayable, or even another closure, which in turn will be
evaluated and merged into the collection of its context.
All closures will receive the current `FluentHtmlElement` instance as their first parameter,
this can be used for pretty advanced conditionals.

```php
// Example with closures and conditions
$show_div = $show_2nd_sentence = $p2_title = false;

echo FluentHtml::create(function () use ($show_div) {
    // The element name itself is generated by this closure with a condition
    // If no element name is returned, no element tag will be printed,
    // but the element contents will
    if ($show_div) {
        return 'div';
    }
})
    ->withClass('wrapper')
    ->containingElement('p')->withAttribute('title', function () {
        return 'p1';
    })->withContent([
        'This is a paragraph.',
        'It could have two sentences.' => $show_2nd_sentence
    ])
    ->followedByElement('p')->withAttribute('title', $p2_title)
    ->withContent(function (FluentHtml $paragraph) {
        // The parameter is the current FluentHtml element,
        // so we can check its properties or related elements' properties
        if ($paragraph->getParentElement()->getContentCount() > 1) {
            return 'This is another paragraph.';
        }
    });
```

```html
<p title="p1">This is a paragraph.</p>
<p>This is another paragraph.</p>
```

### Multiple attribute values
If an html attribute is supplied more than one value, they will be concatenated into a comma-separated list.

```php
// Example with concatenated attribute values
echo FluentHtml::create('meta')->withAttribute('name', 'keywords')
    ->withAttribute('content', ['list', 'of', 'keywords']);
```

```html
<meta name="keywords" content="list,of,keywords">
```

### Usage with [Blade](http://laravel.com/docs/blade) templates
Echoing the result in a template is easy because the string conversion of a [`FluentHtmlElement`] implementation
always returns the full HTML structure from the top element down:

```
{!! FluentHtml::create('div')->containingElement('p')->withContent('Text') !!}
```

Because [`FluentHtml`](src/FluentHtml.php) implements
[`Htmlable`](https://github.com/illuminate/contracts/blob/master/Support/Htmlable.php),
using escaping Blade echo-tags will also work...
*However*, `toHtml()` only returns the rendered element and its contents, 
so this example will only display the last element on the chain, the outer element will be omitted:

```
{{ FluentHtml::create('div')->containingElement('p')->withContent('Text' }}
```

Blade sections are available to yield as content using Blade's `$__env` variable:

```
{{ FluentHtml::create('div')->withRawContent($__env->yieldContent('section_name','Default content')) }}
```

## Methods reference
* [Methods creating new elements](#methods-creating-new-elements)
    - `FluentHtml::create()`
    - `new FluentHtml()`
* [Methods modifying and returning the same element](#methods-modifying-and-returning-the-same-element)
    - [Adding content](#adding-content)
        * [`withContent()`](#withcontenthtml_contents)
        * [`withPrependedContent()`](#withprependedcontenthtml_contents)
        * [`withRawHtmlContent()`](#withrawhtmlcontentraw_html_content)
        * [`withContentWrappedIn()`](#withcontentwrappedinhtml_contents-wrapping_html_element_name-wrapping_tag_attributes--)
        * [`withDefaultContent()`](#withdefaultcontenthtml_contents)
    - [Adding siblings](#adding-siblings)
        * [`withPrecedingSibling()`](#withprecedingsiblinghtml_siblings)
        * [`withFollowingSibling()`](#withfollowingsiblinghtml_siblings)
        * [`withPrecedingRawHtml()`](#withprecedingrawhtmlraw_html_sibling)
        * [`withFollowingRawHtml()`](#withfollowingrawhtmlraw_html_sibling)
    - [Manipulating attributes](#manipulating-attributes)
        * [`withAttribute()`](#withattributeattributes-value--true)
        * [`withoutAttribute()`](#withoutattributeattributes)
        * [`withId()`](#withiddesired_id)
        * [`withClass()`](#withclassclasses)
        * [`withoutClass()`](#withoutclassclasses)
    - [Other fluent methods](#other-fluent-methods)
        * [`withHtmlElementName()`](#withhtmlelementnamehtml_element_name)
        * [`onlyDisplayedIf()`](#onlydisplayedifcondition)
        * [`onlyDisplayedIfHasContent()`](#onlydisplayedifhascontent)
        * [`afterInsertion()`](#afterinsertioncallback)
* [Methods returning a new element relative to the current](#methods-returning-a-new-element-relative-to-the-current)
    - [Inserting within the current element](#inserting-within-the-current-element)
        * [`containingElement()`](#containingelementhtml_element_name--null-tag_contents---tag_attributes--)
        * [`startingWithElement()`](#startingwithelementhtml_element_name-tag_contents---tag_attributes--)
    - [Inserting next to the current element](#inserting-next-to-the-current-element)
        * [`followedByElement()`](#followedbyelementhtml_element_name-tag_contents---tag_attributes--)
        * [`precededByElement()`](#precededbyelementhtml_element_name-tag_contents---tag_attributes--)
    - [Wrapping around the current element](#wrapping-around-the-current-element)
        * [`wrappedInElement()`](#wrappedinelementhtml_element_name--null-tag_attributes--)
        * [`siblingsWrappedInElement()`](#siblingswrappedinelementhtml_element_name-tag_attributes--)
* [Methods for structure navigation](#methods-for-structure-navigation)
    - Finding ancestors
        * [`getParentElement()`](#getparentelement)
        * [`getSiblingsCommonParent()`](#getsiblingscommonparent)
        * [`getRootElement()`](#getrootelement)
        * [`getAncestorInstanceOf()`](#getAncestorInstanceOf)
* [Element state methods](#element-state-methods)
    * [`getId()`](#getiddesired_id--null)
    * [`hasClass()`](#hasclassclass)
    * [`getAttribute()`](#getattributeattribute)
    * [`hasContent()`](#hascontent)
    * [`getContentCount()`](#getcontentcount)
    * [`willRenderInHtml()`](#willrenderinhtml)
        
### Methods creating new elements
The [`FluentHtml`](src/FluentHtml.php) constructor and the static `create()` function share the same signature: 
```php
FluentHtml::create(
    $html_element_name = null,
    $tag_contents = [],
    $tag_attributes = []
)
```

Each [`FluentHtmlElement`](src/FluentHtmlElement.php) instance can be the start of a new chain of fluent method calls
for modifying and adding more elements relative the previous.
This is also true for the returned `FluentHtml` of
[methods returning a new element relative to the current](#methods-returning-a-new-element-relative-to-the-current).

```php
@param string|callable|null $html_element_name
@param string|Htmlable|array|Arrayable $tag_contents
@param array|Arrayable $tag_attributes
```

A falsy `$html_element_name` makes the element render only its contents.
The `$html_element_name` may also be a callable in which case it's evaluated just before rendering
and that callable's return value will be used as the element name.

The optional `$tag_contents` will be inserted in the same manner as
[`withContent()`](#withcontenthtml_contents).

The optional `$tag_attributes` will be inserted in the same manner as
[`withAttribute()`](#withattributeattributes-value--true).

### Methods modifying and returning the same element
These methods can be chained to modify the current element step by step.

#### Adding content
These methods put html content inside the current element.

##### `withContent($html_contents)`
Add html content after existing content in the current element.
Accepts multiple arguments that can be:
* strings (will be escaped)
* objects implementing [`Htmlable`](https://github.com/illuminate/contracts/blob/master/Support/Htmlable.php),
e.g. another instance of `FluentHtmlElement`
* arrayables containing any types from this list (including other arrayables) 
* callables returning any types from this list (including other callables) 

```php
@param string|Htmlable|callable|array|Arrayable $html_contents,...
```

_Alias for `withAppendedContent()`_

##### `withPrependedContent($html_contents)`
Add html content before existing content in the current element.
Same parameter options as [`withContent()`](#withcontenthtml_contents).

##### `withRawHtmlContent($raw_html_content)`
Add a raw string of html content last within this element.

##### `withContentWrappedIn($html_contents, $wrapping_html_element_name, $wrapping_tag_attributes = [])`
Add html contents last within this element, with each inserted new content wrapped in an element.

```php
@param string|Htmlable|callable|array|Arrayable $html_contents,...
@param string|callable $wrapping_html_element_name
@param array|Arrayable $wrapping_tag_attributes
```

##### `withDefaultContent($html_contents)`
Set html content to display as default if no other content is set.
Same parameter options as [`withContent()`](#withcontenthtml_contents).

#### Adding siblings
These methods put html siblings next to the current element
and have the same parameter options as [`withContent()`](#withcontenthtml_contents)
or just one string for the raw html ones.

##### `withPrecedingSibling($html_siblings)`
Add html outside and before this element in the tree.
 
##### `withFollowingSibling($html_siblings)`
Add html outside and after this element in the tree.

##### `withPrecedingRawHtml($raw_html_sibling)`
Add a raw string of html outside and before this element in the tree.

##### `withFollowingRawHtml($raw_html_sibling)`
Add a raw string of html outside and after this element in the tree.

#### Manipulating attributes

##### `withAttribute($attributes, $value = true)`
Add one or more named attributes with value to the current element.
Overrides any set attributes with same name.
Attributes evaluating to falsy will be unset.
Use [`withId()`](#withiddesired_id) and [`withClass()`](#withclassclasses) instead for those attributes.

The first parameter in the simplest form is the attribute name as string,
but it can also be an array of attribute names and values,
or a callable returning such an array.

If the first parameter is an attribute name string, its value is taken from the second parameter.
Boolean values makes the attribute name print only if truthy.
The attribute is omitted from print if the value is falsy. 

##### `withoutAttribute($attributes)`
Remove one or more named attributes from the current element.

```php
@param string|array|Arrayable $attributes,...
```

##### `withId($desired_id)`
Set the id attribute on the current element.
Will check if the desired id is already taken and if so set another unique id.

##### `withClass($classes)`
Add one or more class names to the current element.

```php
@param string|callable|array|Arrayable $classes,...
```

##### `withoutClass($classes)`
Remove one or more class names from the current element.

```php
@param string|array|Arrayable $classes,...
```

#### Other fluent methods

##### `withHtmlElementName($html_element_name)`
Set the html element name. The parameter can be a string or a callable returning a string.

##### `onlyDisplayedIf($condition)`
Will not display current element if any added condition evaluates to false.
The parameter may be a boolean or a callable returning a boolean.

##### `onlyDisplayedIfHasContent()`
Will not display current element if it has no content.
Useful to get rid of empty lists or other containers.

##### `afterInsertion($callback)`
Register callbacks to run after the current element is placed in an element tree.

The closure will receive the current FluentHtml instance as the first parameter,
as with other [closures as input](#closures-as-method-input).

It's usually a good idea to check for some condition on the element before manipulating it within the closure,
because an element may be inserted into other elements many times throughout its lifetime.

### Methods returning a new element relative to the current
These methods creates a new element, adds it relative the current element and returns that new element.
This makes any chained methods switch to operate on the new element after the call.

Most of these methods share signature with the [constructor](#methods-creating-new-elements).

```php
@param string|callable|null $html_element_name
@param string|Htmlable|array|Arrayable $tag_contents
@param array|Arrayable $tag_attributes
```

#### Inserting within the current element

##### `containingElement($html_element_name = null, $tag_contents = [], $tag_attributes = [])`
Adds a new element last among this element's children and returns the new element.

_Alias for endingWithElement()_

##### `startingWithElement($html_element_name, $tag_contents = [], $tag_attributes = [])`
Adds a new element first among this element's children and returns the new element.

#### Inserting next to the current element

##### `followedByElement($html_element_name, $tag_contents = [], $tag_attributes = [])`
Adds a new element just after this element and returns the new element.

##### `precededByElement($html_element_name, $tag_contents = [], $tag_attributes = [])`
Adds a new element just before this element and returns the new element.

#### Wrapping around the current element
The wrapping methods have no parameter for setting contents.
This is of course because the content is already there to be wrapped.

##### `wrappedInElement($html_element_name = null, $tag_attributes = [])`
Wraps only this element in a new element and returns the new element.

##### `siblingsWrappedInElement($html_element_name, $tag_attributes = [])`
Wraps this element together with its siblings in a new element and returns the new element.

### Methods for structure navigation
These methods returns a found existing element or a new empty element put in the requested position.
Useful for referencing other elements in the current tree,
especially within [closures as input](#closures-as-method-input)
but can also be used in a method chain to switch elements.

##### `getParentElement()`
Get or generate the closest parent for this element, even if it's unnamed.

##### `getSiblingsCommonParent()`
Get the closest named parent element or an unnamed parent if none found.
This is the common parent of this element and its siblings as rendered in html.

##### `getRootElement()`
Get the root element of this element's tree.

##### `getAncestorInstanceOf($type)`
Get the closest ancestor in the tree that is an [instance of](http://php.net/manual/en/language.operators.type.php)
the supplied type.
Remember to supply the *fully qualified* class name.
Returns `null` if none found.

### Element state methods
These methods are used to query the properties and states of an element.
Useful for conditionals within [closures as input](#closures-as-method-input).

##### `getId($desired_id = null)`
Get the element's id string if set, or generate a new id.
The optional parameter can be used to try a desired id string which will be used if not already taken,
just like [`withId()`](#withiddesired_id).

##### `hasClass($class)`
Find out if this element will have a specified class when rendered.
The parameter should be a string and the return value is a boolean.

##### `getAttribute($attribute)`
Get the evaluated value of a named attribute.
If an attribute has been set with a callable, it will be evaluated before returning.

The parameter should be a string and the returned value is usually a string or a boolean,
but can be a collection if the attribute has been set with an arrayable.
The returned value is `null` if the attribute hasn't been set.

##### `hasContent()`
Find out if this element will contain any content when rendered.
Will return `true` if this element has content to render after evaluation.

##### `getContentCount()`
Get the number of content pieces in this element. Empty contents are counted too.

##### `willRenderInHtml()`
Find out if this element is set to render.
Returns `false` if any condition for rendering the element fails.

## Development

### Testing
> vendor/bin/phpunit

## Alternatives
https://github.com/spatie/html-element has another interesting way of generating html.

## Authors
I, Björn Nilsved, work at the largest communication agency in southern Sweden.
We call ourselves [FEW](http://fewagency.se) (oh, the irony).
From time to time we have positions open for web developers and programmers in the Malmö/Copenhagen area,
so please get in touch!

## License
The FEW Agency Fluent HTML builder is open-sourced software licensed under the
[MIT license](http://opensource.org/licenses/MIT)