# [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) HTML builder for PHP

* [Introduction](#intro)
    - [What's the point?](#point)
    - [Bootstrap example](#example-bootstrap)
    - [When to use (and not)](#when-to-use)
* [Installation](#install)
* [Usage](#when-to-use)
    - [Collections as input](#usage-collections)
        * [Conditional output](#usage-conditional-output)
    - [Closures as input](#usage-closures)
    - [Multiple attribute values](#usage-multiple-attributes)
    - [Blade templates](#usage-blade)
* [Methods reference](#methods)
* [Authors - FEW Agency](#few)
* [Licence](#licence)

<a id="intro"></a>
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

<a id="when-to-use"></a>
### When to use FluentHtml
Basically, FluentHtml should be used for those cases where you build complex html structures with many if-statements.
Stay with your standard html views or templates for all the simple stuff! 

<a id="install"></a>
## Installation & configuration
> composer require fewagency/fluent-html

### Optional facades
You may add [Laravel facades](http://laravel.com/docs/facades) in the `aliases` array of your project's
`config/app.php` configuration file:

```php
'FluentHtml'  => FewAgency\FluentHtml\Facades\FluentHtml::class,
'HtmlBuilder' => FewAgency\FluentHtml\Facades\HtmlBuilder::class,
```

<a id="dependencies"></a>
### Dependencies
This package takes advantage of the [Collection](https://github.com/illuminate/support/blob/master/Collection.php)
implementation ([docs](http://laravel.com/docs/collections)) and the
[Arrayable](https://github.com/illuminate/contracts/blob/master/Support/Arrayable.php) and
[Htmlable](https://github.com/illuminate/contracts/blob/master/Support/Htmlable.php) interfaces from
[Laravel](http://laravel.com/docs)'s [Illuminate](https://github.com/illuminate) components.

<a id="usage"></a>
## Usage

<a id="usage-collections"></a>
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

<a id="usage-conditional-output"></a>
#### Conditional output
String keys are usually displayed instead of their value if their corresponding evaluated value is truthy.
This makes it possible to conditionally show or hide html contents and element attributes, depending on their value
being true or false.

<a id="usage-closures"></a>
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

<a id="usage-multiple-attributes"></a>
### Multiple attribute values - comma separated lists
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

<a id="methods"></id>
## Methods reference
//TODO: document each group of methods and their usage

<a id="few"></a>
## Authors
I, Björn Nilsved, work at the largest communication agency in southern Sweden.
We call ourselves [FEW](http://fewagency.se) (oh, the irony).
From time to time we have positions open for web developers and programmers in the Malmö/Copenhagen area,
so please get in touch!

## License <a id="licence"></a>
The FEW Agency Fluent HTML builder is open-sourced software licensed under the
[MIT license](http://opensource.org/licenses/MIT)