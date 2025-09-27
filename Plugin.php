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
            'description' => 'A dispatcher component for dynamic AJAX handling in templates.',
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
}
