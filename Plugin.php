<?php namespace Mercator\DynamicAjax;

use System\Classes\PluginBase;
use Log;

/**
 * DynamicAjax Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = [];

    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'DynamicAjax',
            'description' => 'A component to use AJAX in WinterCMS Blocks.',
            'author'      => 'Helmut Kaufmann, software@mercator.li',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Registers the front-end components implemented in this plugin.
     */
    public function registerComponents(): array
    {
	Log::info("ajaxDispatcher registered");
        return [
            'Mercator\DynamicAjax\Components\Dispatcher' => 'ajaxDispatcher',
        ];
    }
    
    public function registerMarkupTags()
    {
        // Define the encryption logic once in a closure.
        $encryptClosure = function ($value) {
            if (is_null($value)) {
                return null;
            }
            return Crypt::encryptString($value);
        };

        return [
            'functions' => [
                // Register 'abCrypt' as a function
                'parCrypt' => $encryptClosure
            ],
            'filters' => [
                // Register 'abCrypt' as a filter
                'parCrypt' => $encryptClosure
            ]
        ];
    }
}
