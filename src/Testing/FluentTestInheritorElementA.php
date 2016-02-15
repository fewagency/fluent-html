<?php
namespace FewAgency\FluentHtml\Testing;

class FluentTestInheritorElementA extends FluentTestInheritorBaseElement
{
    /**
     * @param string|callable|null $html_element_name
     */
    public function __construct($html_element_name = null)
    {
        $child_element = $this->createInstanceOf('FluentTestInheritorElementB');
        parent::__construct($html_element_name, ['A content', $child_element]);
    }

    /**
     * This is for testing requesting a missing subclass
     */
    public function createMissingSubclass()
    {
        return $this->createInstanceOf('MissingSubclass');
    }
}