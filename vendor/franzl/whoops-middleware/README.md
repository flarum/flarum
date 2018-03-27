# PSR-7 middleware for Whoops

A PSR-7 compatible middleware for [Whoops](https://github.com/filp/whoops), the fantastic pretty error handler for PHP.

## Installation

You can install the library using Composer:

    composer require franzl/whoops-middleware

## Usage

### Zend Stratigility

If you're using Zend's Stratigility middleware pipe, you need to use the special error middleware to be able to handle exceptions:

~~~php
$app->pipe(new \Franzl\Middleware\Whoops\ErrorMiddleware);
~~~

(You should probably do this at the end of your middleware stack, so that errors cannot be handled elsewhere.)

### Relay and others

Add the standard middleware to the queue:

~~~php
$queue[] = new \Franzl\Middleware\Whoops\Middleware;
~~~
