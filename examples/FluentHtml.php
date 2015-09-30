<?php
require __DIR__ . '/../vendor/autoload.php';

use FewAgency\FluentHtml\FluentHtml;

echo "\n";

// Simple example
echo FluentHtml::create('div')->withClass('wrapper')
    ->containingElement('p')->withAttribute('id', 'p1')->withContent('This is a paragraph.', 'It has two sentences.')
    ->followedByElement('p')->withAttribute('id', 'p2')->withContent('This is another paragraph.');

echo "\n\n";

// Example with conditions
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

// Bootstrap form-group generated with options
$name = 'username';
$value = 'test@test.com';
$control_id = $name;
$control_help_id = "{$control_id}_help";
$errors['username'] = ["{$name} is required", "{$name} must be a valid email address"];
$help_text = "{$name} is your email address";

// Build the input's help (aria-describedby) element and keep a reference
$control_help = FluentHtml::create('div')->withAttribute('id', $control_help_id)->onlyDisplayedIfHasContent();

// Add any errors as a list in the help element
$control_help->containingElement('ul')->withClass('help-block', 'list-unstyled')->onlyDisplayedIfHasContent()
    ->withContentWrappedIn($errors, 'li', ['class' => 'text-capitalize-first'])
    // Finish the help element with a fixed help message
    ->followedByElement('span', $help_text)->withClass('help-block')->onlyDisplayedIfHasContent();

// Build the input element and keep a reference
$input = FluentHtml::create('input')->withAttribute('type', 'text')->withClass('form-control')
    ->withAttribute(['name' => $name, 'value' => $value, 'id' => $control_id, 'readonly'])
    ->withAttribute('aria-describedby', function () use ($control_help) {
        // Only set the input's aria-describedby attribute if that element has content
        return $control_help->hasContent() ? $control_help->getAttribute('id') : false;
    });

// Wrap up and print the full result
echo $input->siblingsWrappedInElement('div')->withClass('input-group')
    // Add a label before the input-group, defaulting to the input name if label not specified
    ->precededByElement('label', empty($label) ? $name : $label)->withClass('control-label')
    ->withAttribute('for', function () use ($input) {
        return $input->getAttribute('id');
    })
    // Wrap the label and input-group in a form-group
    ->siblingsWrappedInElement('div')->withClass('form-group')
    ->withClass(function () use ($errors) {
        // Set the validation state class on the form-group
        if (count($errors)) {
            return 'has-error';
        }
    })
    // Add the help element last in the form-group
    ->withAppendedContent($control_help);

echo "\n\n";