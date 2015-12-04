<?php namespace Revolverobotics\HelperTools\Facades;

use Illuminate\Support\Facades\Facade;

class HelperTools extends Facade
{
    /**
     * Name of the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'helpertools';
    }
}
