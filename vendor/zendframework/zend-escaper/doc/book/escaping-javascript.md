# Escaping Javascript

Javascript string literals in HTML are subject to significant restrictions due
to the potential for unquoted attributes and uncertainty as to whether
Javascript will be viewed as being `CDATA` or `PCDATA` by the browser. To
eliminate any possible XSS vulnerabilities, Javascript escaping for HTML extends
the escaping rules of both ECMAScript and JSON to include any potentially
dangerous character. Very similar to HTML attribute value escaping, this means
escaping everything except basic alphanumeric characters and the comma, period,
and underscore characters as hexadecimal or unicode escapes.

Javascript escaping applies to all literal strings and digits. It is not
possible to safely escape other Javascript markup.

To escape data in the **Javascript context**, use `Zend\Escaper\Escaper`'s
`escapeJs()` method. An extended set of characters are escaped beyond
ECMAScript's rules for Javascript literal string escaping in order to prevent
misinterpretation of Javascript as HTML leading to the injection of special
characters and entities.

## Example of Bad Javascript Escaping

An example of incorrect Javascript escaping:

```php
<?php header('Content-Type: application/xhtml+xml; charset=UTF-8'); ?>
<!DOCTYPE html>
<?php
$input = <<<INPUT
bar&quot;; alert(&quot;Meow!&quot;); var xss=&quot;true
INPUT;

$output = json_encode($input);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Unescaped Entities</title>
    <meta charset="UTF-8"/>
    <script type="text/javascript">
        <?php
        // this will result in
        // var foo = "bar&quot;; alert(&quot;Meow!&quot;); var xss=&quot;true";
        ?>
        var foo = <?= $output ?>;
    </script>
</head>
<body>
    <p>json_encode() is not good for escaping javascript!</p>
</body>
</html>
```

The above example will show an alert popup box as soon as the page is loaded,
because the data is not properly escaped for the Javascript context.

## Example of Good Javascript Escaping

By using the `escapeJs()` method in the Javascript context, such attacks can be
prevented:

```php
<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<?php
$input = <<<INPUT
bar&quot;; alert(&quot;Meow!&quot;); var xss=&quot;true
INPUT;

$escaper = new Zend\Escaper\Escaper('utf-8');
$output = $escaper->escapeJs($input);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Escaped Entities</title>
    <meta charset="UTF-8"/>
    <script type="text/javascript">
        <?php
        // this will look like
        // var foo =
bar\x26quot\x3B\x3B\x20alert\x28\x26quot\x3BMeow\x21\x26quot\x3B\x29\x3B\x20var\x20xss\x3D\x26quot\x3Btrue;
        ?>
        var foo = <?= $output ?>;
    </script>
</head>
<body>
    <p>Zend\Escaper\Escaper::escapeJs() is good for escaping javascript!</p>
</body>
</html>
```

In the above example, the Javascript parser will most likely report a
`SyntaxError`, but at least the targeted application remains safe from such
attacks.
