# Escaping Cascading Style Sheets

CSS is similar to [escaping Javascript](escaping-javascript.md).  CSS escaping
excludes only basic alphanumeric characters and escapes all other characters
into valid CSS hexadecimal escapes.

## Example of Bad CSS Escaping

In most cases developers forget to escape CSS completely:

```php
<?php header('Content-Type: application/xhtml+xml; charset=UTF-8'); ?>
<!DOCTYPE html>
<?php
$input = <<<INPUT
body {
    background-image: url('http://example.com/foo.jpg?</style><script>alert(1)</script>');
}
INPUT;
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Unescaped CSS</title>
    <meta charset="UTF-8"/>
    <style>
    <?= $input ?>
    </style>
</head>
<body>
    <p>User controlled CSS needs to be properly escaped!</p>
</body>
</html>
```

In the above example, by failing to escape the user provided CSS, an attacker
can execute an XSS attack fairly easily.

## Example of Good CSS Escaping

By using `escapeCss()` method in the CSS context, such attacks can be prevented:

```php
<?php header('Content-Type: application/xhtml+xml; charset=UTF-8'); ?>
<!DOCTYPE html>
<?php
$input = <<<INPUT
body {
    background-image: url('http://example.com/foo.jpg?</style><script>alert(1)</script>');
}
INPUT;

$escaper = new Zend\Escaper\Escaper('utf-8');
$output = $escaper->escapeCss($input);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Escaped CSS</title>
    <meta charset="UTF-8"/>
    <style>
    <?php
    // output will look something like
    // body\20 \7B \A \20 \20 \20 \20 background\2D image\3A \20 url\28 ...
    echo $output;
    ?>
    </style>
</head>
<body>
    <p>User controlled CSS needs to be properly escaped!</p>
</body>
</html>
```

By properly escaping user controlled CSS, we can prevent XSS attacks in our web
applications.
