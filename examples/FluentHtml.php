<?php
require __DIR__ . '/../vendor/autoload.php';

use FewAgency\FluentHtml\FluentHtml;

echo "\n";

//Simple example
echo FluentHtml::create('div')->withClass('wrapper')
    ->containingElement('p')->withAttribute('id', 'p1')->withContent('This is a paragraph.', 'It has two sentences.')
    ->followedByElement('p')->withAttribute('id', 'p2')->withContent('This is another paragraph.');

echo "\n\n";

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

echo "\n\n";

//Bootstrap form-group with options
$name = 'username';
$control_id = $name;
$control_help_id = "{$control_id}_help";
$errors['username'] = ["{$name} is required", "{$name} must be a valid email address"];
$help_text = "{$name} is your email address";

$control_help = FluentHtml::create('div')->withAttribute('id', $control_help_id)->onlyDisplayedIfHasContent();
$control_help->containingElement('ul')->onlyDisplayedIfHasContent()->withClass('help-block', 'list-unstyled')
    ->withContentWrappedIn($errors, 'li', ['class' => 'text-capitalize-first'])
    ->followedByElement('span', $help_text)->withClass('help-block')->onlyDisplayedIfHasContent();

echo FluentHtml::create('input')->withAttribute('type', 'text')->withClass('form-control')
    ->withAttribute('name', $name)->withAttribute('id', $control_id)
    ->withAttribute('aria-describedby', function () use ($control_help) {
        return $control_help->hasContent() ? $control_help->getAttribute('id') : false;
    })
    ->siblingsWrappedInElement('div')->withClass('input-group')
    ->precededByElement('label', empty($label) ? $name : $label)->withClass('control-label')
    ->siblingsWrappedInElement('div')->withClass('form-group')
    ->withAppendedContent($control_help);

echo "\n\n";