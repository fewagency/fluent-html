# [Fluent interface](https://en.wikipedia.org/wiki/Fluent_interface) HTML builder for PHP

```php
//Simple example
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
building, like this:

```php
//Example with conditions
$show_div = $show_2nd_sentence = $p2_id = false;

echo FluentHtml::create(function () use ($show_div) {
    if ($show_div) {
        return 'div';
    }
})->withClass('wrapper')
    ->containingElement('p')->withAttribute('id', function () {
        return 'p1';
    })->withContent(['This is a paragraph.', 'It may have two sentences.' => $show_2nd_sentence])
    ->followedByElement('p')->withAttribute('id', $p2_id)->withContent(function () {
        return 'This is another paragraph.';
    });
```

...which prints like this when the conditions are falsy:

```html
<p id="p1">This is a paragraph.</p>
<p>This is another paragraph.</p>
```

Basically, it should be used for those cases where you build complex html structures with many if-statements.
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
TODO: write usage section

### Collections as method input
Most methods accept arrays or Arrayable collections (and other implementations of Arrayable) as input parameters.
Values may then also be another such collection, in which case the whole collection is recursively flattened
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
All closures receive the current `FluentHtml` instance as their first parameter, this can be used for pretty advanced
conditionals.

### Multiple attribute values
If an html attribute is supplied more than one value, they will be concatenated into a comma-separated list.

## Authors
I, Björn Nilsved, work at largest communication agency in southern Sweden. We call ourselves [FEW](http://fewagency.se) (oh, the irony).
From time to time we have positions open for web developers and programmers in the Malmö/Copenhagen area, so please get in touch!

## License
The Fluent HTML builder is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)