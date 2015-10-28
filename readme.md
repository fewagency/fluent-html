# [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) HTML builder for PHP

* [Introduction](#introduction)
    - [What's the point?](#point)
    - [Advanced Bootstrap example](#example-bootstrap)
    - [When to use (and not)](#when-to-use-fluenthtml)
* [Installation](#installation--configuration)
* [Usage](#usage)
    - [Collections as input](#collections-as-method-input)
        * [Conditional output](#conditional-output)
    - [Closures as input](#closures-as-method-input)
    - [Multiple attribute values](#multiple-attribute-values)
    - [Blade templates](#usage-blade)
* [Methods reference](#methods-reference)
* [Authors - FEW Agency](#authors)
* [Licence](#licence)

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
The power of FluentHtml comes from the ability to add collections of values, closures and conditions to the html
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

Generating the above in a PHP template could be a hassle, with if-statements repeated all over the place.
Very prone to errors sneaking in.
Using FluentHtml the code would probably take about the same space, but it would be a lot more readable,
guaranteed to print correct and well-formatted HTML, and can be split in manageable and reusable chunks, like this:

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

### When to use FluentHtml
Basically, FluentHtml should be used for those cases where you build complex html structures with many if-statements.
Stay with your standard html views or templates for all the simple stuff! 

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
This package takes advantage of the [Collection](https://github.com/illuminate/support/blob/master/Collection.php)
implementation ([docs](http://laravel.com/docs/collections)) and the
[Arrayable](https://github.com/illuminate/contracts/blob/master/Support/Arrayable.php) and
[Htmlable](https://github.com/illuminate/contracts/blob/master/Support/Htmlable.php) interfaces from
[Laravel](http://laravel.com/docs)'s [Illuminate](https://github.com/illuminate) components.

## Usage

### Collections as method input
Most methods accept arrays or Arrayable collections (and other implementations of Arrayable) as input parameters.
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
When a closure is evaluated it may return a value, boolean, Arrayable, or even another closure, which in turn will be
evaluated and merged into the collection of its context.
All closures will receive the current `FluentHtml` instance as their first parameter, this can be used for pretty
advanced conditionals.

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

<a id="usage-blade"></a>
### Usage with [Blade](http://laravel.com/docs/blade) templates
Echoing the result in a template is easy because the string conversion of a FluentHtml instance always returns
the full HTML structure from the top element down:

```
{!! FluentHtml::create('p')->withContent('Text') !!}
```

Blade sections are available to yield as content using Blade's `$__env` variable:

```
{!! FluentHtml::create('div')->withRawContent($__env->yieldContent('section_name','Default content')) !!}
```

## Methods reference
//TODO: document each group of methods and their usage

### Methods creating new elements
The `FluentHtml` constructor and the static function 
`create($html_element_name = null, $tag_contents = [], $tag_attributes = [])`
share the same signature.

Each `FluentHtml` instance can be the start of a new chain of fluent method calls
for modifying and adding more elements relative the previous.

```php
@param string|callable|null $html_element_name
@param string|Htmlable|array|Arrayable $tag_contents
@param array|Arrayable $tag_attributes
```

A blank `$html_element_name` makes the element render only its contents.
The `$html_element_name` may also be a callable in which case it's evaluated just before rendering
and that callable's return value will be used as the element name.

The optional `$tag_contents` will be inserted in the same manner as `withContent()`.

The optional `$tag_attributes` will be inserted in the same manner as `withAttribute()`.

### Methods modifying and returning the same element
These methods can be chained to modify the current element step by step.

#### Methods adding content

##### `withContent($html_contents)`
Add html content after existing content in the current element.

Accepts multiple arguments that can be
* strings (will be escaped)
* objects implementing `Htmlable`
* arrayables

...or callables returning any of those types. 

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

#### Methods for attributes

##### `withAttribute($attributes, $value = true)`
Add one or more named attributes with value to the current element.
Overrides any set attributes with same name.
Attributes evaluating to falsy will be unset.

_Use `withId()` and `withClass()` instead for those attributes._

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

### Methods returning a new element relative to the current
These methods creates a new element, adds it relative to the current element and returns that new element.
This makes any chained methods switch to operate on the new element after the call.

#### Methods inserting within the current element

##### containingElement

##### startingWithElement

#### Methods inserting next to the current element

##### followedByElement

##### precededByElement

#### Methods inserting around the current element

##### wrappedInElement

##### siblingsWrappedInElement

### Methods for structure navigation
These methods returns a found existing element or a new empty element.
Useful for referencing other elements in the current tree,
especially within [closures as input](#usage-closures)
but can also be used in a method chain to switch elements.

##### getParentElement

##### getSiblingsCommonParent

##### getRootElement

### Element state methods
These methods are used to query the properties and states of an element.
Useful for conditionals within [closures as input](#usage-closures).

##### getId

##### hasClass

##### getAttribute

##### hasContent

##### getContentCount

##### willRenderInHtml

##### isRootElement

## Authors
I, Björn Nilsved, work at the largest communication agency in southern Sweden.
We call ourselves [FEW](http://fewagency.se) (oh, the irony).
From time to time we have positions open for web developers and programmers in the Malmö/Copenhagen area,
so please get in touch!

## License
The FEW Agency Fluent HTML builder is open-sourced software licensed under the
[MIT license](http://opensource.org/licenses/MIT)