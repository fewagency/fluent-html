<?php
namespace FewAgency\FluentHtml\Testing;

use FewAgency\FluentHtml\FluentHtml;

abstract class FluentTestInheritorBaseElement extends FluentHtml
{
    /**
     * Overridden to make sure the called class is reported correctly even when overriding
     * @param string $classname
     * @param array $parameters
     * @return \FewAgency\FluentHtml\FluentHtmlElement
     */
    protected function createInstanceOf($classname, $parameters = [])
    {
        return parent::createInstanceOf($classname, $parameters);
    }
}