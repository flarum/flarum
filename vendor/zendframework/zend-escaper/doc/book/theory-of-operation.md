# Theory of Operation

zend-escaper provides methods for escaping output data, dependent on the context
in which the data will be used. Each method is based on peer-reviewed rules and
is in compliance with the current OWASP recommendations.

The escaping follows a well-known and fixed set of encoding rules defined by
OWASP for each key HTML context.  These rules cannot be impacted or negated by
browser quirks or edge-case HTML parsing unless the browser suffers a
catastrophic bug in its HTML parser or Javascript interpreter &mdash; both of
these are unlikely.

The contexts in which zend-escaper should be used are **HTML Body**, **HTML
Attribute**, **Javascript**, **CSS**, and **URL/URI** contexts.

Every escaper method will take the data to be escaped, make sure it is utf-8
encoded data (or try to convert it to utf-8), perform context-based escaping,
encode the escaped data back to its original encoding, and return the data to
the caller.

The actual escaping of the data differs between each method; they all have their
own set of rules according to which escaping is performed. An example will allow
us to clearly demonstrate the difference, and how the same characters are being
escaped differently between contexts:

```php
$escaper = new Zend\Escaper\Escaper('utf-8');

// &lt;script&gt;alert(&quot;zf2&quot;)&lt;/script&gt;
echo $escaper->escapeHtml('<script>alert("zf2")</script>');

// &lt;script&gt;alert&#x28;&quot;zf2&quot;&#x29;&lt;&#x2F;script&gt;
echo $escaper->escapeHtmlAttr('<script>alert("zf2")</script>');

// \x3Cscript\x3Ealert\x28\x22zf2\x22\x29\x3C\x2Fscript\x3E
echo $escaper->escapeJs('<script>alert("zf2")</script>');

// \3C script\3E alert\28 \22 zf2\22 \29 \3C \2F script\3E 
echo $escaper->escapeCss('<script>alert("zf2")</script>');

// %3Cscript%3Ealert%28%22zf2%22%29%3C%2Fscript%3E
echo $escaper->escapeUrl('<script>alert("zf2")</script>');
```

More detailed examples will be given in later chapters.

## The Problem with Inconsistent Functionality

At present, programmers orient towards the following PHP functions for each
common HTML context:

- **HTML Body**: `htmlspecialchars()` or `htmlentities()`
- **HTML Attribute**: `htmlspecialchars()` or `htmlentities()`
- **Javascript**: `addslashes()` or `json_encode()`
- **CSS**: n/a
- **URL/URI**: `rawurlencode()` or `urlencode()`

In practice, these decisions appear to depend more on what PHP offers, and if it
can be interpreted as offering sufficient escaping safety, than it does on what
is recommended in reality to defend against XSS. While these functions can
prevent some forms of XSS, they do not cover all use cases or risks and are
therefore insufficient defenses.

Using `htmlspecialchars()` in a perfectly valid HTML5 unquoted attribute value,
for example, is completely useless since the value can be terminated by a space
(among other things), which is never escaped. Thus, in this instance, we have a
conflict between a widely used HTML escaper and a modern HTML specification,
with no specific function available to cover this use case. While it's tempting
to blame users, or the HTML specification authors, escaping just needs to deal
with whatever HTML and browsers allow.

Using `addslashes()`, custom backslash escaping, or `json_encode()` will
typically ignore HTML special characters such as ampersands, which may be used
to inject entities into Javascript. Under the right circumstances, the browser
will convert these entities into their literal equivalents before interpreting
Javascript, thus allowing attackers to inject arbitrary code.

Inconsistencies with valid HTML, insecure default parameters, lack of character
encoding awareness, and misrepresentations of what functions are capable of by
some programmers &mdash; these all make escaping in PHP an unnecessarily
convoluted quest.

To circumvent the lack of escaping methods in PHP, zend-escaper addresses the
need to apply context-specific escaping in web applications. It implements
methods that specifically target XSS and offers programmers a tool to secure
their applications without misusing other inadequate methods, or using, most
likely incomplete, home-grown solutions.

## Why Contextual Escaping?

To understand why multiple standardised escaping methods are needed, what
follows are several quick points; they are by no means a complete set of
reasons, however!

### HTML escaping of unquoted HTML attribute values still allows XSS

This is probably the best known way to defeat `htmlspecialchars()` when used on
attribute values, since any space (or character interpreted as a space &mdash;
there are a lot) lets you inject new attributes whose content can't be
neutralised by HTML escaping. The solution (where this is possible) is
additional escaping as defined by the OWASP ESAPI codecs. The point here can be
extended further &mdash; escaping only works if a programmer or designer knows
what they're doing. In many contexts, there are additional practices and gotchas
that need to be carefully monitored since escaping sometimes needs a little
extra help to protect against XSS &mdash; even if that means ensuring all
attribute values are properly double quoted despite this not being required for
valid HTML.

### HTML escaping of CSS, Javascript or URIs is often reversed when passed to non-HTML interpreters by the browser

HTML escaping is just that &mdsash; it's designed to escape a string for HTML
(i.e. prevent tag or attribute insertion), but not alter the underlying meaning
of the content, whether it be text, Javascript, CSS, or URIs. For that purpose,
a fully HTML-escaped version of any other context may still have its unescaped
form extracted before it's interpreted or executed. For this reason we need
separate escapers for Javascript, CSS, and URIs, and developers or designers
writing templates **must** know which escaper to apply to which context. Of
course, this means you need to be able to identify the correct context before
selecting the right escaper!

### DOM-based XSS requires a defence using at least two levels of different escaping in many cases

DOM-based XSS has become increasingly common as Javascript has taken off in
popularity for large scale client-side coding. A simple example is Javascript
defined in a template which inserts a new piece of HTML text into the DOM. If
the string is only HTML escaped, it may still contain Javascript that will
execute in that context. If the string is only Javascript-escaped, it may
contain HTML markup (new tags and attributes) which will be injected into the
DOM and parsed once the inserting Javascript executes. Damned either way? The
solution is to escape twice &mdash; first escape the string for HTML (make it
safe for DOM insertion), and then for Javascript (make it safe for the current
Javascript context). Nested contexts are a common means of bypassing naive
escaping habits (e.g. you can inject Javascript into a CSS expression within an
HTML attribute).

### PHP has no known anti-XSS escape functions (only those kidnapped from their original purposes)

A simple example, widely used, is when you see `json_encode()` used to escape
Javascript, or worse, some kind of mutant `addslashes()` implementation. These
were never designed to eliminate XSS, yet PHP programmers use them as such. For
example, `json_encode()` does not escape the ampersand or semi-colon characters
by default. That means you can easily inject HTML entities which could then be
decoded before the Javascript is evaluated in a HTML document. This lets you
break out of strings, add new JS statements, close tags, etc. In other words,
using `json_encode()` is insufficient and naive. The same, arguably, could be
said for `htmlspecialchars()` which has its own well known limitations that make
a singular reliance on it a questionable practice.
