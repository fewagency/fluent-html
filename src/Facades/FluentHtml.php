<?php namespace FewAgency\FluentHtml\Facades;

use Illuminate\Support\Facades\Facade;

class FluentHtml extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'FewAgency\FluentHtml\FluentHtml';
    }
}