# Escaping URLs

This method is basically an alias for PHP's `rawurlencode()` which has applied
RFC 3986 since PHP 5.3. It is included primarily for consistency.

URL escaping applies to data being inserted into a URL and not to the whole URL
itself.

## Example of Bad URL Escaping

XSS attacks are easy if data inserted into URLs is not escaped properly:

```php
<?php header('Content-Type: application/xhtml+xml; charset=UTF-8'); ?>
<!DOCTYPE html>
<?php
$input = <<<INPUT
" onmouseover="alert('zf2')
INPUT;
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Unescaped URL data</title>
    <meta charset="UTF-8"/>
</head>
<body>
    <a href="http://example.com/?name=<?= $input ?>">Click here!</a>
</body>
</html>
```

## Example of Good URL Escaping

By properly escaping data in URLs by using `escapeUrl()`, we can prevent XSS
attacks:

```php
<?php header('Content-Type: application/xhtml+xml; charset=UTF-8'); ?>
<!DOCTYPE html>
<?php
$input = <<<INPUT
" onmouseover="alert('zf2')
INPUT;

$escaper = new Zend\Escaper\Escaper('utf-8');
$output = $escaper->escapeUrl($input);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Unescaped URL data</title>
    <meta charset="UTF-8"/>
</head>
<body>
    <a href="http://example.com/?name=<?= $output ?>">Click here!</a>
</body>
</html>
```
