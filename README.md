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
-   **`parCrypt` Twig Function & Filter**: A helper to easily encrypt any data structure (strings, arrays, etc.) directly in your markup.

---

## Installation & Setup

### 1. Plugin Installation

1.  Place the plugin files into a new directory: `/plugins/mercator/ajaxdispatcher/`.
2.  Run `php artisan winter:up` to register the plugin with the system.

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

* **Procedural Function Call**: `'filename::functionName'`
* **Class Method Call**: `'filename::Namespace\ClassName::methodName'`

### `parCrypt` Twig Function & Filter

This plugin provides the `parCrypt` helper, available as both a function and a filter, to easily encrypt values for secure parameter handling. **You must wrap the call in `{{ ... }}` to execute it.**

> **Note on Salting**: Winter's encryption automatically includes a unique signature and Initialization Vector (IV) with every encrypted value. This serves the same purpose as a salt, ensuring that the output is always unique and secure without requiring you to manage salts manually.

**As a function:**

```twig
data-request-data="encrypted_recordId: '{{ parCrypt(123) }}'"
```

**As a filter:**

```twig
data-request-data="encrypted_recordId: '{{ 123 | parCrypt }}'"
```

### Secure Parameters via Encryption

For sensitive data like record IDs that you don't want the user to be able to modify, you can use parameter encryption. The dispatcher will automatically decrypt any parameter key that is prefixed with `encrypted_`.

1.  **In your markup**, use the `parCrypt` function or filter (wrapped in `{{ }}`) to encrypt the value.
2.  The **Dispatcher** receives the request, detects the `encrypted_` prefix, decrypts the value.
3.  The clean, original value is passed to your PHP handler.

---

## Complete Walkthrough Example

This example demonstrates a form, a class-based handler, and a secure action using the `parCrypt` filter.

### Step 1: Create the PHP Logic

Place the following files in `/themes/your-theme/blocks/`.

#### `greeterClass.php` (Class-based)

```php
<?php
namespace Greeter;

class GreeterActions {
    public function sayHello($userName) {
        $sanitizedName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        return ['#resultDiv' => '<p style="color: green;">Hello, ' . $sanitizedName . '!</p>'];
    }
}
```

#### `deleter.php` (Secure Handler)

```php
<?php
function onDelete($recordId) {
    // In a real app, you would delete the record.
    return ['#resultDiv' => '<p style="color: red;">Deleted record with ID: ' . e($recordId) . '</p>'];
}
```

### Step 2: Create the Block Markup

Use this markup in a block or partial.

```twig
<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">
    <h3>Greet a custom name (Class Method)</h3>
    <form
        data-request="ajaxDispatcher::onRequest"
        data-request-data="handler: 'greeterClass::Greeter\GreeterActions::sayHello'">
        <input type="text" name="userName" placeholder="Enter a name">
        <button type="submit" data-attach-loading>Greet Me</button>
    </form>
</div>

<div style="border: 1px solid #ddd; padding: 20px;">
    <h3>Secure Delete Action</h3>
    <p>The record ID (123) is encrypted directly in the markup using a Twig filter.</p>
    <button
        type="button"
        data-request="ajaxDispatcher::onRequest"
        data-request-confirm="Are you sure?"
        data-request-data="handler: 'deleter::onDelete', encrypted_recordId: '{{ 123 | parCrypt }}'"
        data-attach-loading>
        Delete Item #123
    </button>
</div>

<div id="resultDiv" style="margin-top: 20px; padding: 15px; text-align: center;">
    </div>
```

---

## License

The MIT License (MIT). Please see the `LICENSE` file for more information.
