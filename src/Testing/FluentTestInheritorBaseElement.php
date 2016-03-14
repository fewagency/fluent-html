<?php
namespace FewAgency\FluentHtml\Testing;

use FewAgency\FluentHtml\FluentHtml;

class FluentTestInheritorBaseElement extends FluentHtml
{
    /**
     * Overridden to make sure the called class is reported correctly even when overriding
     * and to make instance creation publicly accessible.
     * @param string $classname
     * @param array $parameters
     * @return \FewAgency\FluentHtml\FluentHtmlElement
     */
    public function createInstanceOf($classname, $parameters = [])
    {
        return parent::createInstanceOf($classname, $parameters);
    }
}