<?php namespace Mercator\DynamicAjax\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Theme;
use ApplicationException;
use ReflectionMethod;
use ReflectionFunction;
use Log;
use Crypt;
use Winter\Storm\Support\Str;

class Dispatcher extends ComponentBase
{
    public function componentDetails(): array
    {
        return [
            'name'        => 'AJAX Dispatcher',
            'description' => 'Calls functions or class methods from files in the blocks directory of the current theme.'
        ];
    }

    public function onRequest()
    {
        $handler = post('handler');
        $separatorCount = substr_count($handler, '::');
        // Log::info ("Ajax Dispatcher onRequest: $handler");

        if ($separatorCount === 1) {
            // Procedural function call: "file::function"
            list($fileName, $functionName) = explode('::', $handler, 2);
            return $this->handleFunctionCall($fileName, $functionName);
        }

        if ($separatorCount === 2) {
            // Class method call: "file::Namespace\Class::method"
            list($fileName, $className, $methodName) = explode('::', $handler, 3);
            return $this->handleMethodCall($fileName, $className, $methodName);
        }

        throw new ApplicationException('Invalid handler format. Expected "file::function" or "file::Class::method".');
    }

    protected function handleFunctionCall($fileName, $functionName)
    {
        $handlerPath = $this->getHandlerPath($fileName);
        require_once $handlerPath;

        if (!function_exists($functionName)) {
            throw new ApplicationException(sprintf('AJAX handler function [%s()] not found.', e($functionName)));
        }

        $reflection = new ReflectionFunction($functionName);
        $args = $this->resolveParameters($reflection->getParameters());

        return call_user_func_array($functionName, $args);
    }

    protected function handleMethodCall($fileName, $className, $methodName)
    {
        $handlerPath = $this->getHandlerPath($fileName);
        require_once $handlerPath;

        if (!class_exists($className)) {
            throw new ApplicationException(sprintf('AJAX handler class [%s] not found.', e($className)));
        }

        $instance = new $className();

        if (!method_exists($instance, $methodName)) {
            throw new ApplicationException(sprintf('AJAX handler method [%s] not found in class [%s].', e($methodName), e($className)));
        }

        $reflection = new ReflectionMethod($className, $methodName);
        $args = $this->resolveParameters($reflection->getParameters());

        return call_user_func_array([$instance, $methodName], $args);
    }

    protected function getHandlerPath($fileName)
    {
        $safeFileName = basename($fileName);

        $candidates = [
            Theme::getActiveTheme()->getPath() . '/blocks/' . $safeFileName . '.php',
            plugins_path('mercator/blocks/blocks/' . $safeFileName . '.php'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new ApplicationException(sprintf('AJAX handler file [%s.php] not found.', e($safeFileName)));
    }

    protected function resolveParameters(array $parameters)
    {
        $argsToPass = [];
        $postData = post();

        // Look for and decrypt any parameters prefixed with 'encrypted_'
        $decryptedData = [];
        foreach ($postData as $key => $value) {
            if (Str::startsWith($key, 'encrypted_')) {
                try {
                    // Get the new key name (e.g., 'encrypted_recordId' becomes 'recordId')
                    $newKey = Str::after($key, 'encrypted_');
                    $decryptedData[$newKey] = Crypt::decrypt($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    throw new ApplicationException('Could not decrypt a required parameter.');
                }
            }
        }

        // Merge decrypted data, giving it priority over any non-encrypted versions
        $postData = array_merge($postData, $decryptedData);

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            if (array_key_exists($paramName, $postData)) {
                $argsToPass[] = $postData[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                $argsToPass[] = $param->getDefaultValue();
            } else {
                throw new ApplicationException(sprintf("Missing required parameter: '%s'", $paramName));
            }
        }

        return $argsToPass;
    }
}
