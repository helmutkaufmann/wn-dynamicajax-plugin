# AJAX Dispatcher — Mercator plugin for Winter CMS

A utility component that lets you call PHP functions and class methods from block markup via AJAX, without writing a full Winter CMS component. A single `ajaxDispatcher` component routes all theme-level AJAX requests to the right handler.

[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

---

## Installation & Setup

### 1. Plugin

Place the plugin files in:

```
plugins/mercator/dynamicajax
```

Then run:

```bash
php artisan winter:up
```

### 2. Frontend dependencies

Include jQuery and the Winter CMS AJAX framework in your layout, before `</body>`:

```twig
<script src="{{ 'assets/javascript/jquery.js' | theme }}"></script>
{% framework extras %}
```

### 3. Attach the component

Add `ajaxDispatcher` to any page or layout where you want to use it:

```twig
[ajaxDispatcher]
```

---

## How It Works

### The handler string

Pass a `handler` key via `data-request-data` to tell the dispatcher what to call. It looks for the PHP file in:

1. `themes/your-theme/blocks/<filename>.php`
2. `plugins/mercator/blocks/blocks/<filename>.php`

**Procedural function:**
```
handler: 'filename::functionName'
```

**Class method:**
```
handler: 'filename::Namespace\ClassName::methodName'
```

### Automatic parameter injection

The dispatcher matches POST keys and `data-request-data` values to your PHP function's parameter names. No manual `post()` calls needed.

### Secure parameters with `parCrypt`

Prefix any parameter with `encrypted_` and the dispatcher will automatically decrypt it before passing it to your handler. Use the `parCrypt` Twig function or filter to encrypt values in your markup:

```twig
{{-- as a function --}}
data-request-data="encrypted_recordId: '{{ parCrypt(123) }}'"

{{-- as a filter --}}
data-request-data="encrypted_recordId: '{{ 123 | parCrypt }}'"
```

---

## Example

### PHP handlers

Place these in `themes/your-theme/blocks/`.

**`greeterClass.php`** (class method):

```php
<?php
namespace Greeter;

class GreeterActions {
    public function sayHello($userName) {
        return ['#resultDiv' => '<p>Hello, ' . e($userName) . '!</p>'];
    }
}
```

**`deleter.php`** (procedural, with encrypted parameter):

```php
<?php
function onDelete($recordId) {
    // $recordId is already decrypted
    return ['#resultDiv' => '<p>Deleted record #' . e($recordId) . '</p>'];
}
```

### Block markup

```twig
<form
    data-request="ajaxDispatcher::onRequest"
    data-request-data="handler: 'greeterClass::Greeter\GreeterActions::sayHello'">
    <input type="text" name="userName" placeholder="Enter a name">
    <button type="submit" data-attach-loading>Greet Me</button>
</form>

<button
    type="button"
    data-request="ajaxDispatcher::onRequest"
    data-request-confirm="Are you sure?"
    data-request-data="handler: 'deleter::onDelete', encrypted_recordId: '{{ 123 | parCrypt }}'"
    data-attach-loading>
    Delete Item #123
</button>

<div id="resultDiv"></div>
```

---

## License

The MIT License (MIT). See `LICENSE`.
