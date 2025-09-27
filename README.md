# AJAX Dispatcher Plugin for Winter CMS Blocks

A powerful utility component for Winter CMS that allows you to call PHP functions and class methods directly from your theme files via AJAX. This plugin provides a centralized dispatcher, eliminating the need to create separate components for simple, theme-level AJAX interactions.

[](https://github.com/wintercms/wn-blocks-plugin/blob/main/LICENSE)

-----

## Core Concept

In a typical Winter CMS workflow, adding AJAX functionality to the frontend requires defining a component and an AJAX handler within it. While powerful, this can be cumbersome for small, repeated interactions, especially in a block-based or modular theme design.

This plugin solves that problem by providing a single, reusable component (`ajaxDispatcher`) that acts as a router for your theme-level AJAX logic. You can keep your PHP logic in simple `.php` files within your theme, and call them directly from your frontend markup using `data-` attributes. This is ideal for:

  * **Block-based themes**: Allowing each block to have its own self-contained logic.
  * **Simple interactions**: Adding dynamic functionality without the boilerplate of a full component.
  * **Rapid prototyping**: Quickly wiring up server-side logic to your frontend.

-----

## Features

  * **Centralized AJAX Handling**: A single component manages all your theme-level AJAX requests.
  * **Flexible Handler Calls**: Execute both procedural functions and class methods.
  * **Automatic Parameter Injection**: The dispatcher intelligently matches data from your request (form inputs, `data-request-data`) to the parameters of your PHP handler function by name.
  * **Clean Frontend Markup**: Keeps your `.block` or `.htm` files focused on presentation, with clear and declarative AJAX triggers.
  * **Reduces Boilerplate**: Avoid creating numerous single-purpose components for simple tasks.

-----

## Installation & Setup

### 1\. Plugin Installation

1.  Copy the plugin files into a new directory: `/plugins/mercator/ajaxdispatcher/`.

2.  Run the database migrations to register the plugin with the system.

    ```bash
    php artisan winter:up
    ```

### 2\. Frontend Dependencies

For the AJAX functionality to work, your pages must include **jQuery** and the **WinterCMS AJAX framework**. Place the following tags in your CMS layout or page, typically before the closing `</body>` tag. The `extras` parameter is recommended for features like loading indicators and flash messages.

```twig
<script src="{{ 'assets/javascript/jquery.js' | theme }}"></script>
{% framework extras %}
```

### 3\. Attaching the Component

Attach the `AJAX Dispatcher` component to any page or layout where you intend to use it. This makes the `ajaxDispatcher::onRequest` handler available.

```twig
[ajaxDispatcher]
```

-----

## How It Works

The dispatcher's core functionality is driven by the `handler` key, which you pass via `data-request-data`. This string tells the dispatcher what code to execute.

### The Handler String

The handler string follows a specific format using `::` as a separator.

#### 1\. Procedural Function Call

This format is ideal for simple, self-contained functions.

  * **Format**: `'filename::functionName'`
  * **Example**: `handler: 'greeter::sayHello'`
      * `greeter`: The dispatcher will look for a file named `greeter.php`.
      * `sayHello`: The dispatcher will call the `sayHello()` function within that file.

#### 2\. Class Method Call

This format is better for organizing more complex logic in an object-oriented way.

  * **Format**: `'filename::Namespace\ClassName::methodName'`
  * **Example**: `handler: 'greeterClass::Greeter\GreeterActions::sayGoodbye'`
      * `greeterClass`: The dispatcher will look for a file named `greeterClass.php`.
      * `Greeter\GreeterActions`: The fully namespaced class to instantiate.
      * `sayGoodbye`: The method to call on the new class instance.

### Parameter Resolution

The dispatcher automatically provides your PHP functions with the data they need. It inspects the parameters of your function/method and looks for matching keys in the POST data sent by the AJAX request.

If your PHP function is `sayHello($userName)`, the dispatcher will look for `userName` in the form inputs or `data-request-data` and pass its value to the `$userName` parameter.

-----

## Complete Usage Example

This example demonstrates all features of the dispatcher.

### PHP Handler Files

Create the following two files in your theme's `/blocks/` directory.

#### `/themes/your-theme/blocks/greeter.php`

```php
<?php

/**
 * Procedural function for simple actions.
 */
function sayHello($userName)
{
    // Sanitize the name for display.
    $sanitizedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

    $greeting = '<p style="color: green;">A special hello to ' . $sanitizedName . '!</p>';

    // The key is a CSS selector, the value is the HTML to inject.
    return ['#greetingResult' => $greeting];
}
```

#### `/themes/your-theme/blocks/greeterClass.php`

```php
<?php
namespace Greeter;

class GreeterActions
{
    /**
     * Class-based method for more organized logic.
     */
    public function sayGoodbye($userName = 'friend')
    {
        $sanitizedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

        $goodbye = '<p style="color: blue;">Goodbye for now, ' . $sanitizedName . '!</p>';

        return ['#greetingResult' => $goodbye];
    }
}
```

### Block / Partial Markup

Use this markup in a block or partial. Remember to attach the `ajaxDispatcher` component to the page/layout.

```twig
<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">
    <h3>Example 1: Greet a pre-defined person (Procedural)</h3>
    <p>These buttons call the `sayHello` function in `greeter.php`.</p>
    <div>
        <button
            type="button"
            data-request="ajaxDispatcher::onRequest"
            data-request-data="handler: 'greeter::sayHello', userName: 'Mary'"
            data-attach-loading>
            Greet Mary
        </button>
    </div>
</div>

<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">
    <h3>Example 2: Greet a custom name from an input (Procedural)</h3>
    <form
        data-request="ajaxDispatcher::onRequest"
        data-request-data="handler: 'greeter::sayHello'"
    >
        <div>
            <input type="text" name="userName" placeholder="Enter a name" style="width: 100%; padding: 8px;">
        </div>
        <button type="submit" data-attach-loading style="margin-top: 15px;">
            Greet Me
        </button>
    </form>
</div>

<div style="border: 1px solid #ddd; padding: 20px;">
    <h3>Example 3: Say Goodbye (Class Method)</h3>
    <p>This button calls the `sayGoodbye` method on the `GreeterActions` class in `greeterClass.php`.</p>
    <button
        type="button"
        style="background-color: #d9534f; color: white; border: 1px solid #d43f3a;"
        data-request="ajaxDispatcher::onRequest"
        data-request-data="handler: 'greeterClass::Greeter\GreeterActions::sayGoodbye', userName: 'Admin'"
        data-attach-loading>
        Say Goodbye to Admin
    </button>
</div>

<div id="greetingResult" style="margin-top: 20px; padding: 15px; font-size: 1.2em; text-align: center; border: 1px solid #eee; min-height: 50px;">
    </div>
```

-----

## License

The MIT License (MIT). Please see [License File](https://www.google.com/search?q=LICENSE) for more information.
