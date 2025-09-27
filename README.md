# AJAX Dispatcher Plugin for Winter CMS Blocks

![Blocks Plugin Banner](https://github.com/wintercms/wn-blocks-plugin/blob/main/.github/banner.png?raw=true)

A powerful utility component for Winter CMS that allows you to call PHP functions and class methods directly from your theme files via AJAX. This plugin provides a centralized dispatcher, eliminating the need to create separate components for simple, theme-level AJAX interactions.

[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

## Core Concept

In a typical Winter CMS workflow, adding AJAX functionality to the frontend requires defining a component and an AJAX handler within it. While powerful, this can be cumbersome for small, repeated interactions, especially in a block-based or modular theme design.

This plugin solves that problem by providing a single, reusable component (`ajaxDispatcher`) that acts as a router for your theme's AJAX logic. You can keep your PHP logic in simple `.php` files within your theme, and call them directly from your frontend markup using `data-` attributes.

---

## Features

-   **Centralized AJAX Handling**: A single component manages all your theme-level AJAX requests.
-   **Flexible Handler Calls**: Execute both procedural functions and class methods.
-   **Automatic Parameter Injection**: The dispatcher intelligently matches data from your request (form inputs, `data-request-data`) to the parameters of your PHP handler function by name.
-   **Secure Parameter Handling**: Automatically decrypts parameters prefixed with `encrypted_` to prevent client-side tampering of sensitive data like record IDs.
-   **Clean Frontend Markup**: Keeps your `.block` or `.htm` files focused on presentation, with clear and declarative AJAX triggers.
-   **Reduces Boilerplate**: Avoid creating numerous single-purpose components for simple tasks.

---

## Installation & Setup

### 1. Plugin Installation

1.  Place the plugin files into a new directory: `/plugins/mercator/dynamicajax/`.
2.  Register the component in your `Plugin.php` file with the alias `ajaxDispatcher`.
3.  Run `php artisan winter:up` to register the plugin with the system.

### 2. Frontend Dependencies

For the AJAX functionality to work on your pages, you must include **jQuery** and the **WinterCMS AJAX framework**. Place the following tags in your CMS layout or page, typically before the closing `</body>` tag. The `extras` parameter is recommended for features like loading indicators and flash messages.

```twig
<script src="{{ 'assets/javascript/jquery.js' | theme }}"></script>
{% framework extras %}
```

### 3. Attaching the Component

Attach the `AJAX Dispatcher` component to any page or layout where you intend to use its functionality. This makes the `ajaxDispatcher::onRequest` handler available to your frontend markup.

```twig
[ajaxDispatcher]
```

---

## How It Works

### The Handler String

The dispatcher's core functionality is driven by the `handler` key, which you pass via `data-request-data`. This string tells the dispatcher what code to execute from your theme's `/blocks/` directory.

#### Procedural Function Call

-   **Format**: `'filename::functionName'`
-   **Example**: `handler: 'greeter::sayHello'`
    -   `greeter`: The dispatcher will load the file `greeter.php`.
    -   `sayHello`: The dispatcher will call the `sayHello()` function within that file.

#### Class Method Call

-   **Format**: `'filename::Namespace\ClassName::methodName'`
-   **Example**: `handler: 'greeterClass::Greeter\GreeterActions::sayGoodbye'`
    -   `greeterClass`: The dispatcher will load the file `greeterClass.php`.
    -   `Greeter\GreeterActions`: The fully namespaced class to instantiate.
    -   `sayGoodbye`: The method to call on the new class instance.

### Passing Parameters

The dispatcher automatically provides your PHP handlers with the data they need by inspecting the function/method signature and matching parameter names with keys in the POST data. Data can come from:

-   Standard `<input>` fields within a submitted `<form>`.
-   The `data-request-data` attribute.

### Secure Parameters via Encryption

For sensitive data like record IDs that you don't want the user to be able to modify in their browser, you can use parameter encryption.

> **Important**: This feature is for **preventing client-side tampering**, not for securing data in transit. For transit security, you **must use HTTPS**.

The dispatcher will automatically decrypt any parameter key that is prefixed with `encrypted_`.

1.  **On the server (page render)**, encrypt your data using `Crypt::encryptString()`.
2.  **In your markup**, send this encrypted string with the `encrypted_` prefix (e.g., `encrypted_recordId`).
3.  **The Dispatcher** receives the request, detects the prefix, decrypts the value, and passes the clean, original value to your handler (e.g., `onDelete($recordId)`).

### Returning Data

Your PHP handlers should return data in the format expected by the WinterCMS AJAX framework. Typically, this is an array where keys are CSS selectors and values are the new HTML content for those selectors.

```php
return ['#myDiv' => 'New content here!'];
```

---

## Complete Walkthrough Example

This example demonstrates all features, including a secure delete button.

### Step 1: Create the PHP Logic

Place the following files in `/themes/your-theme/blocks/`.

#### `greeter.php` (Procedural)

```php
<?php
function sayHello($userName) {
    $sanitizedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    return ['#resultDiv' => '<p style="color: green;">Hello, ' . $sanitizedName . '!</p>'];
}
```

#### `greeterClass.php` (Class-based)

```php
<?php
namespace Greeter;

class GreeterActions {
    public function sayGoodbye($userName = 'friend') {
        $sanitizedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        return ['#resultDiv' => '<p style="color: blue;">Goodbye, ' . $sanitizedName . '!</p>'];
    }
}
```

#### `deleter.php` (Secure Handler)

```php
<?php
function onDelete($recordId) {
    // In a real app, you would delete the record.
    // MyModel::destroy($recordId);
    return ['#resultDiv' => '<p style="color: red;">Deleted record with ID: ' . e($recordId) . '</p>'];
}
```

### Step 2: Create the Block Markup

Create a block file (`.block` or `.htm`) that uses the handlers. This example includes a PHP section to encrypt the ID for the secure delete button.

```twig
name: Dispatcher Examples
tags: ["pages"]
==
<?php
function onStart()
{
    // Encrypt a record ID for the secure delete button.
    $this['secureId'] = Crypt::encryptString(123);
}
?>
==
<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">
    <h3>Greet a custom name</h3>
    <form data-request="ajaxDispatcher::onRequest" data-request-data="handler: 'greeter::sayHello'">
        <input type="text" name="userName" placeholder="Enter a name">
        <button type="submit" data-attach-loading>Greet Me</button>
    </form>
</div>

<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">
    <h3>Say Goodbye (Class Method)</h3>
    <button type="button" data-request="ajaxDispatcher::onRequest" data-request-data="handler: 'greeterClass::Greeter\GreeterActions::sayGoodbye', userName: 'Admin'" data-attach-loading>
        Say Goodbye
    </button>
</div>

<div style="border: 1px solid #ddd; padding: 20px;">
    <h3>Secure Delete Action</h3>
    <button
        type="button"
        data-request="ajaxDispatcher::onRequest"
        data-request-confirm="Are you sure?"
        data-request-data="handler: 'deleter::onDelete', encrypted_recordId: '{{ secureId }}'"
        data-attach-loading>
        Delete Item #123
    </button>
</div>

<div id="resultDiv" style="margin-top: 20px; padding: 15px; font-size: 1.2em; text-align: center; border: 1px solid #eee;">
    </div>
```

---

## License

The MIT License (MIT). Please see the `LICENSE` file for more information.
