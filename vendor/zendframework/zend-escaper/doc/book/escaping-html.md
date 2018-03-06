# Escaping HTML

Probably the most common escaping happens for **HTML body** contexts. There are
very few characters with special meaning in this context, yet it is quite common
to escape data incorrectly, namely by setting the wrong flags and character
encoding.

For escaping data to use within an HTML body context, use
`Zend\Escaper\Escaper`'s `escapeHtml()` method.  Internally it uses PHP's
`htmlspecialchars()`, correctly setting the flags and encoding for you.

```php
// Outputting this without escaping would be a bad idea!
$input = '<script>alert("zf2")</script>';

$escaper = new Zend\Escaper\Escaper('utf-8');

// somewhere in an HTML template
<div class="user-provided-input">
    <?= $escaper->escapeHtml($input) // all safe! ?>
</div>
```

One thing a developer needs to pay special attention to is the encoding in which
the document is served to the client, as it **must be the same** as the encoding
used for escaping!

## Example of Bad HTML Escaping

An example of incorrect usage:

```php
<?php
$input = '<script>alert("zf2")</script>';
$escaper = new Zend\Escaper\Escaper('utf-8');
?>
<?php header('Content-Type: text/html; charset=ISO-8859-1'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Encodings set incorrectly!</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
</head>
<body>
<?php 
    // Bad! The escaper's and the document's encodings are different!
    echo $escaper->escapeHtml($input);
?>
</body>
```

## Example of Good HTML Escaping

An example of correct usage:

```php
<?php
$input = '<script>alert("zf2")</script>';
$escaper = new Zend\Escaper\Escaper('utf-8');
?>
<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Encodings set correctly!</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<?php 
    // Good! The escaper's and the document's encodings are same!
    echo $escaper->escapeHtml($input);
?>
</body>
```
