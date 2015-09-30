# [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) HTML builder for PHP

```php
// Simple example
echo FluentHtml::create('div')->withClass('wrapper')
    ->containingElement('p')->withAttribute('id', 'p1')->withContent('This is a paragraph.', 'It has two sentences.')
    ->followedByElement('p')->withAttribute('id', 'p2')->withContent('This is another paragraph.');
```

Woha, that's a very elaborate way of writing:

```html
<div class="wrapper">
<p id="p1">
This is a paragraph.
It has two sentences.
</p>
<p id="p2">This is another paragraph.</p>
</div>
```

So, then what's the point of it all?
The power of FluentHtml comes from the ability to add collections of values, closures and conditions to the html
building process.
When the complexity grows you can build elements step by step and and trust the end result to be correct and
well-formatted HTML in every situation.

For example when generating [Bootstrap form-groups](http://getbootstrap.com/css/#forms) for an input or
[input-group](http://getbootstrap.com/components/#input-groups) with label,
[validation states](http://getbootstrap.com/css/#forms-control-validation), and
[help-text](http://getbootstrap.com/css/#forms-help-text)
...the desired HTML would look like this:

```html
<div class="form-group has-error">
<label class="control-label" for="username">username</label>
<div class="input-group">
<span class="input-group-addon"><input type="checkbox" aria-label="Addon checkbox"></span>
<input type="text" class="form-control" name="username" value="test@test.com" id="username" readonly aria-describedby="username_help">
<span class="input-group-btn"><button class="btn btn-default" type="button">Go!</button></span>
</div>
<div id="username_help">
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
$control_id = $name;
$control_help_id = "{$control_id}_help";
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
$control_help = FluentHtml::create('div')->withAttribute('id', $control_help_id)->onlyDisplayedIfHasContent();

// Add any errors relevant to the input as a list in the help element
$control_help->containingElement('ul')->withClass('help-block', 'list-unstyled')->onlyDisplayedIfHasContent()
    ->withContentWrappedIn($errors[$name], 'li', ['class' => 'text-capitalize-first'])
    // Put the fixed message at the end of the help element
    ->followedByElement('span', $help_text)->withClass('help-block')->onlyDisplayedIfHasContent();

// Build the input element and keep a reference
$input = FluentHtml::create('input')->withAttribute('type', 'text')->withClass('form-control')
    ->withAttribute(['name' => $name, 'value' => $value, 'id' => $control_id, 'readonly'])
    ->withAttribute('aria-describedby', function () use ($control_help) {
        // Only set the input's aria-describedby attribute if the help element has any content
        if ($control_help->hasContent()) {
            return $control_help->getAttribute('id');
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
        return $input->getAttribute('id');
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

Basically, FluentHtml should be used for those cases where you build complex html structures with many if-statements.
Stay with your standard html views or templates for all the simple stuff! 

This package takes advantage of the [Collection](https://github.com/illuminate/support/blob/master/Collection.php)
implementation ([docs](http://laravel.com/docs/collections)) and the
[Arrayable](https://github.com/illuminate/contracts/blob/master/Support/Arrayable.php) and
[Htmlable](https://github.com/illuminate/contracts/blob/master/Support/Htmlable.php) interfaces from
[Laravel](http://laravel.com/docs)'s [Illuminate](https://github.com/illuminate) components.

## Installation & configuration
> composer require fewagency/fluent-html

### Optional facades
You may add [Laravel facades](http://laravel.com/docs/facades) in the `aliases` array of your project's
`config/app.php` configuration file:

```php
'FluentHtml'  => FewAgency\FluentHtml\Facades\FluentHtml::class,
'HtmlBuilder' => FewAgency\FluentHtml\Facades\HtmlBuilder::class,
```

## Usage

### Collections as method input
Most methods accept arrays or Arrayable collections (and other implementations of Arrayable) as input parameters.
Values may sometimes also be nested collection, in which case the whole collection is recursively flattened
(with preserved associative keys).
When flattening a collection, any duplicate associative keys will be merged over by those appearing later in the
collection.
Values with numeric keys are always appended.

#### Conditional output
String keys are usually displayed instead of their value if their corresponding evaluated value is truthy.
This makes it possible to conditionally show or hide html contents and element attributes, depending on their value
being true or false.

### Closures as method input
Most values can be [PHP closures](http://php.net/manual/en/functions.anonymous.php) in which case their evaluation is
deferred as long as possible, usually until the object is rendered as a string.
When a closure is evaluated it may return a value, boolean, Arrayable, or even another closure, which in turn will be
evaluated and merged into the collection of it's context.
All closures will receive the current `FluentHtml` instance as their first parameter, this can be used for pretty advanced
conditionals.

//TODO: add example of closure using parameter

### Multiple attribute values
If an html attribute is supplied more than one value, they will be concatenated into a comma-separated list.

### Usage with [Blade](http://laravel.com/docs/blade) templates
Echoing the result in a template is easy because the string conversion of a FluidHtml instance always returns the full
HTML structure from the top element down:
`{!! FluidHtml::create('p')->withContent('Text') !!}`

//TODO: describe yielding Blade sections with $__env->yieldContent('section_name','Default content')

## Authors
I, Björn Nilsved, work at the largest communication agency in southern Sweden. We call ourselves [FEW](http://fewagency.se) (oh, the irony).
From time to time we have positions open for web developers and programmers in the Malmö/Copenhagen area, so please get in touch!

## License
The FEW Agency Fluent HTML builder is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)