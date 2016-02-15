<?php
namespace FewAgency\FluentHtml\Testing;

use FewAgency\FluentHtml\FluentHtml;

class FluentTestInheritorElementB extends FluentHtml
{
    /**
     * @param string|callable|null $html_element_name
     */
    public function __construct($html_element_name = null)
    {
        $child_element = $this->createInstanceOf('FluentHtml', ['p', 'B sub content']);
        parent::__construct($html_element_name, ['B content', $child_element]);
    }
}