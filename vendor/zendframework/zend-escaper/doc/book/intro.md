# Introduction

The [OWASP Top 10 web security risks](https://www.owasp.org/index.php/Top_10_2010-Main)
study lists Cross-Site Scripting (XSS) in second place. PHP's sole functionality
against XSS is limited to two functions of which one is commonly misapplied.
Thus, the zend-escaper component was written. It offers developers a way to
escape output and defend from XSS and related vulnerabilities by introducing
**contextual escaping based on peer-reviewed rules**.

zend-escaper was written with ease of use in mind, so it can be used completely stand-alone from
the rest of the framework, and as such can be installed with Composer:

```bash
$ composer install zendframework/zend-escaper
```

Several Zend Framework components provide integrations for consuming
zend-escaper, including [zend-view](https://github.com/zendframework/zend-view),
which provides a set of helpers that consume it.

> ### Security
>
> zend-escaper is a security related component. As such, if you believe you have
> found an issue, we ask that you follow our [Security  Policy](http://framework.zend.com/security/)
> and report security issues accordingly. The Zend Framework team and the
> contributors thank you in advance.

## Overview

zend-escaper provides one class, `Zend\Escaper\Escaper`, which in turn provides
five methods for escaping output. Which method to use  depends on the context in
which the output is used. It is up to the developer to use the right methods in
the right context.

`Zend\Escaper\Escaper` has the following escaping methods available for each context:

- `escapeHtml`: escape a string for an HTML body context.
- `escapeHtmlAttr`: escape a string for an HTML attribute context.
- `escapeJs`: escape a string for a Javascript context.
- `escapeCss`: escape a string for a CSS context.
- `escapeUrl`: escape a string for a URI or URI parameter context.

Usage of each method will be discussed in detail in later chapters.

## What zend-Escaper is not

zend-escaper is meant to be used only for *escaping data for output*, and as
such should not be misused for *filtering input data*. For such tasks, use
[zend-filter](https://zendframework.github.io/zend-filter/),
[HTMLPurifier](http://htmlpurifier.org/) or PHP's
[Filter](http://php.net/filter) functionality should be used.
