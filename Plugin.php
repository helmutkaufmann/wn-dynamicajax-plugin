<?php namespace Mercator\DynamicAjax;

use System\Classes\PluginBase;
use Crypt;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'AJAX Dispatcher',
            'description' => 'Provides a centralized component for handling AJAX requests from theme files.',
            'author'      => 'MyAuthor',
            'icon'        => 'icon-bolt'
        ];
    }

    /**
     * Register the AJAX Dispatcher component.
     */
    public function registerComponents()
    {
        return [
            'Mercator\DynamicAjax\Components\Dispatcher' => 'ajaxDispatcher'
        ];
    }

    /**
     * Register custom Twig functions and filters.
     */
    public function registerMarkupTags()
    {
        // Define the encryption logic once in a closure.
        $encryptClosure = function ($value) {
            if (is_null($value)) {
                return null;
            }
            // Use encrypt() to handle arrays and other data types.
            return Crypt::encrypt($value);
        };

        return [
            'functions' => [
                // Register 'parCrypt' as a function
                'parCrypt' => $encryptClosure
            ],
            'filters' => [
                // Register 'parCrypt' as a filter
                'parCrypt' => $encryptClosure
            ]
        ];
    }
}
