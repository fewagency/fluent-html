<?php namespace FewAgency\FluentHtml\Facades;

use Illuminate\Support\Facades\Facade;

class HtmlBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'FewAgency\FluentHtml\HtmlBuilder';
    }
}