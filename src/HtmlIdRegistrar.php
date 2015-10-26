<?php namespace FewAgency\FluentHtml;


/**
 * Keeps track of used HTML element id strings.
 */
class HtmlIdRegistrar extends IdRegistrar
{
    /**
     * @param string|null $desired_id to check if taken, defaults to "HtmlIdRegistrar1"
     * @return string id to use, guaranteed to be unique in this registrar
     */
    public function unique($desired_id = null)
    {
        if (empty($desired_id)) {
            //Generate a default id string
            $desired_id = class_basename($this) . '1';
        }

        return parent::unique($desired_id);
    }

}