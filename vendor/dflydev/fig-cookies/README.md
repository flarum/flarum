FIG Cookies
===========

Managing Cookies for PSR-7 Requests and Responses.

[![Latest Stable Version](https://poser.pugx.org/dflydev/fig-cookies/v/stable)](https://packagist.org/packages/dflydev/fig-cookies)
[![Total Downloads](https://poser.pugx.org/dflydev/fig-cookies/downloads)](https://packagist.org/packages/dflydev/fig-cookies)
[![Latest Unstable Version](https://poser.pugx.org/dflydev/fig-cookies/v/unstable)](https://packagist.org/packages/dflydev/fig-cookies)
[![License](https://poser.pugx.org/dflydev/fig-cookies/license)](https://packagist.org/packages/dflydev/fig-cookies)
<br>
[![Build Status](https://travis-ci.org/dflydev/dflydev-fig-cookies.svg?branch=master)](https://travis-ci.org/dflydev/dflydev-fig-cookies)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dflydev/dflydev-fig-cookies/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/dflydev/dflydev-fig-cookies/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/dflydev/dflydev-fig-cookies/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/dflydev/dflydev-fig-cookies/?branch=master)
[![Code Climate](https://codeclimate.com/github/dflydev/dflydev-fig-cookies/badges/gpa.svg)](https://codeclimate.com/github/dflydev/dflydev-fig-cookies)
<br>
[![Join the chat at https://gitter.im/dflydev/dflydev-fig-cookies](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/dflydev/dflydev-fig-cookies?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


Requirements
------------

 * PHP 5.4+
 * [psr/http-message](https://packagist.org/packages/psr/http-message)


Installation
------------

```bash
$> composer require dflydev/fig-cookies
```


Concepts
--------

FIG Cookies tackles two problems, managing **Cookie** *Request* headers and
managing **Set-Cookie** *Response* headers. It does this by way of introducing
a `Cookies` class to manage collections of `Cookie` instances and a
`SetCookies` class to manage collections of `SetCookie` instances.

Instantiating these collections looks like this:

```php
// Get a collection representing the cookies in the Cookie headers
// of a PSR-7 Request.
$cookies = Dflydev\FigCookies\Cookies::fromRequest($request);

// Get a collection representing the cookies in the Set-Cookie headers
// of a PSR-7 Response
$setCookies = Dflydev\FigCookies\SetCookies::fromResponse($response);
```

After modifying these collections in some way, they are rendered into a
PSR-7 Request or PSR-7 Response like this:

```php
// Render the Cookie headers and add them to the headers of a
// PSR-7 Request.
$request = $cookies->renderIntoCookieHeader($request);

// Render the Set-Cookie headers and add them to the headers of a
// PSR-7 Response.
$response = $setCookies->renderIntoSetCookieHeader($response);
```

Like PSR-7 Messages, `Cookie`, `Cookies`, `SetCookie`, and `SetCookies`
are all represented as immutable value objects and all mutators will
return new instances of the original with the requested changes.

While this style of design has many benefits it can become fairly
verbose very quickly. In order to get around that, FIG Cookies provides
two facades in an attempt to help simply things and make the whole process
less verbose.


Basic Usage
-----------

The easiest way to start working with FIG Cookies is by using the
`FigRequestCookies` and `FigResponseCookies` classes. They are facades to the
primitive FIG Cookies classes. Their jobs are to make common cookie related
tasks easier and less verbose than working with the primitive classes directly.

There is overhead on creating `Cookies` and `SetCookies` and rebuilding
requests and responses. Each of the `FigCookies` methods will go through this
process so be wary of using too many of these calls in the same section of
code. In some cases it may be better to work with the primitive FIG Cookies
classes directly rather than using the facades.


### Request Cookies

Requests include cookie information in the **Cookie** request header. The
cookies in this header are represented by the `Cookie` class.

```php
use Dflydev\FigCookies\Cookie;

$cookie = Cookie::create('theme', 'blue');
```

To easily work with request cookies, use the `FigRequestCookies` facade.

#### Get a Request Cookie

The `get` method will return a `Cookie` instance. If no cookie by the specified
name exists, the returned `Cookie` instance will have a `null` value.

The optional third parameter to `get` sets the value that should be used if a
cookie does not exist.

```php
use Dflydev\FigCookies\FigRequestCookies;

$cookie = FigRequestCookies::get($request, 'theme');
$cookie = FigRequestCookies::get($request, 'theme', 'default-theme');
```

#### Set a Request Cookie

The `set` method will either add a cookie or replace an existing cookie.

The `Cookie` primitive is used as the second argument.

```php
use Dflydev\FigCookies\FigRequestCookies;

$request = FigRequestCookies::set($request, Cookie::create('theme', 'blue'));
```

#### Modify a Request Cookie

The `modify` method allows for replacing the contents of a cookie based on the
current cookie with the specified name. The third argument is a `callable` that
takes a `Cookie` instance as its first argument and is expected to return a
`Cookie` instance.

If no cookie by the specified name exists, a new `Cookie` instance with a
`null` value will be passed to the callable.

```php
use Dflydev\FigCookies\FigRequestCookies;

$modify = function (Cookie $cookie) {
    $value = $cookie->getValue();

    // ... inspect current $value and determine if $value should
    // change or if it can stay the same. in all cases, a cookie
    // should be returned from this callback...

    return $cookie->withValue($value);
}

$request = FigRequestCookies::modify($request, 'theme', $modify);
```

#### Remove a Request Cookie

The `remove` method removes a cookie if it exists.

```php
use Dflydev\FigCookies\FigRequestCookies;

$request = FigRequestCookies::remove($request, 'theme');
```

### Response Cookies

Responses include cookie information in the **Set-Cookie** response header. The
cookies in these headers are represented by the `SetCookie` class.

```php
use Dflydev\FigCookies\SetCookie;

$setCookie = SetCookie::create('lu')
    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
    ->withMaxAge(500)
    ->withPath('/')
    ->withDomain('.example.com')
    ->withSecure(true)
    ->withHttpOnly(true)
;
```

To easily work with response cookies, use the `FigResponseCookies` facade.

#### Get a Response Cookie

The `get` method will return a `SetCookie` instance. If no cookie by the
specified name exists, the returned `SetCookie` instance will have a `null`
value.

The optional third parameter to `get` sets the value that should be used if a
cookie does not exist.

```php
use Dflydev\FigCookies\FigResponseCookies;

$setCookie = FigResponseCookies::get($response, 'theme');
$setCookie = FigResponseCookies::get($response, 'theme', 'simple');
```

#### Set a Response Cookie

The `set` method will either add a cookie or replace an existing cookie.

The `SetCookie` primitive is used as the second argument.

```php
use Dflydev\FigCookies\FigResponseCookies;

$response = FigResponseCookies::set($response, SetCookie::create('token')
    ->withValue('a9s87dfz978a9')
    ->withDomain('example.com')
    ->withPath('/firewall')
);
```

#### Modify a Response Cookie

The `modify` method allows for replacing the contents of a cookie based on the
current cookie with the specified name. The third argument is a `callable` that
takes a `SetCookie` instance as its first argument and is expected to return a
`SetCookie` instance.

If no cookie by the specified name exists, a new `SetCookie` instance with a
`null` value will be passed to the callable.

```php
use Dflydev\FigCookies\FigResponseCookies;

$modify = function (SetCookie $setCookie) {
    $value = $setCookie->getValue();

    // ... inspect current $value and determine if $value should
    // change or if it can stay the same. in all cases, a cookie
    // should be returned from this callback...

    return $setCookie
        ->withValue($newValue)
        ->withExpires($newExpires)
    ;
}

$response = FigResponseCookies::modify($response, 'theme', $modify);
```

#### Remove a Response Cookie

The `remove` method removes a cookie from the response if it exists.

```php
use Dflydev\FigCookies\FigResponseCookies;

$response = FigResponseCookies::remove($response, 'theme');
```


FAQ
---

### Do you call `setcookies`?

No.

Delivery of the rendered `SetCookie` instances is the responsibility of the
PSR-7 client implementation.


### Do you do anything with sessions?

No.

It would be possible to build session handling using cookies on top of FIG
Cookies but it is out of scope for this package.


### Do you read from `$_COOKIES`?

No.

FIG Cookies only pays attention to the `Cookie` headers on PSR-7 Request
instances. In the case of `ServerRequestInterface` instances, PSR-7
implementations should be including `$_COOKIES` values in the headers
so in that case FIG Cookies may be interacting with `$_COOKIES`
indirectly.


License
-------

MIT, see LICENSE.


Community
---------

Want to get involved? Here are a few ways:

 * Find us in the #dflydev IRC channel on irc.freenode.org.
 * Mention [@dflydev](https://twitter.com/dflydev) on Twitter.
 * [![Join the chat at https://gitter.im/dflydev/dflydev-fig-cookies](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/dflydev/dflydev-fig-cookies?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
