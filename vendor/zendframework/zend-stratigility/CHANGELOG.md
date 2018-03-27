# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

Versions prior to 1.0 were originally released as `phly/conduit`; please visit
its [CHANGELOG](https://github.com/phly/conduit/blob/master/CHANGELOG.md) for
details.

## 1.3.3 - 2017-01-23

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#86](https://github.com/zendframework/zend-stratigility/pull/86) fixes the
  links to documentation in several exception messages to ensure they will be
  useful to developers.

## 1.3.2 - 2017-01-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#95](https://github.com/zendframework/zend-stratigility/pull/95) fixes an
  issue with how the `$err` is dealt with. Specifically, if an error arises,
  then subsequent middlewares should be dispatched as callables. Without this
  fix, stratigility would simply continue dispatching middlewares, ignoring
  the failing ones.

## 1.3.1 - 2016-11-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#85](https://github.com/zendframework/zend-stratigility/pull/85) fixes an
  issue with how the `$done` or `$nextDelegate` is invoked by `Next` when an
  error is present. Previously, the class was detecting a `Next` instance as an
  http-interop `DelegateInterface` instance and dropping the error; this would
  then mean if the instance contained error middleware, it would never be
  dispatched.

## 1.3.0 - 2016-11-10

### Added

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) adds a new
  class, `Zend\Stratigility\Middleware\NotFoundHandler`. This class may be piped
  into an application at an innermost layer; when invoked, it will return a 404
  plain text response.

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) adds a new
  class, `Zend\Stratigility\Middleware\ErrorHandler`. This class may be piped
  into an application, typically at the outermost or one of the outermost
  layers. When invoked, it does the following:

  - Creates a PHP error handler that will re-throw PHP errors as
    `ErrorExceptions`.
  - Dispatches to the next layer.
  - If the next layer does not return a response, it raises a new
    `MissingResponseException`.
  - Catches all exceptions from calling the next layer, and passes them to an
    error response generator to return an error response.

  A default error response generator is provided, which will return a 5XX series
  response in plain text. You may provide a callable generator to the
  constructor in order to customize the response generated; please refer to the
  documentation for details.

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) adds a new
  class, `Zend\Stratigility\NoopFinalHandler`. This class may be provided as the
  `$out` argument to a `MiddlewarePipe`, or as the final handler to
  `Zend\Diactoros\Server::listen()` (in which case it will be passed to the
  middleware you invoke as the application). This handler returns the response
  provided to it verbatim.

- [#70](https://github.com/zendframework/zend-stratigility/pull/70) adds a new
  class, `Zend\Stratigility\Middleware\OriginalMessages`. Compose this
  middleware in an outermost layer, and it will inject the following attributes
  in the request passed to nested layers:

  - `originalRequest`, representing the request provided to it.
  - `originalResponse`, representing the response provided to it.
  - `originalUri`, representing URI instance composed in the request provided to it.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) adds support
  for [http-interop middleware 0.2.0](https://github.com/http-interop/http-middleware/tree/ff545c87e97bf4d88f0cb7eb3e89f99aaa53d7a9).
  For full details, see the [migration guide](https://docs.zendframework.com/zend-stratigility/migration/to-v2/#http-interop-compatibility).
  As a summary of features:
  - You may now pipe http-interop middleware to `MiddlewarePipe` instances.
  - You may now pipe callable middleware that defines the same signature as
    http-interop middleware to `MiddlewarePipe` instances; these will be
    decorated in a `Zend\Stratigility\Middleware\CallableInteropMiddlewareWrapper`
    instance.
  - `MiddlewarePipe` now implements the http-interop
    `ServerMiddlewareInterface`, allowing it to be used in http-interop
    middleware dispatchers.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) adds the
  class `Zend\Stratigility\Middleware\CallableMiddlewareWrapper`. It accepts
  callable double-pass middleware and a response prototype, and implements the
  http-interop `ServerMiddlewareInterface`, allowing you to adapt existing
  callable middleware to work with http-interop middleware dispatchers.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) adds the
  class `Zend\Stratigility\Middleware\CallableInteropMiddlewareWrapper`. It accepts
  callable middleware that follows the http-interop `ServerMiddlewareInterface`,
  and implements that interface itself, to allow composing such middleware in
  http-interop middleware dispatchers.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) adds the
  class `Zend\Stratigility\Delegate\CallableDelegateDecorator`, which can be
  used to add http-interop middleware support to your existing callable
  middleware.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) adds a new
  method to `MiddlewarePipe`, `setResponseProtoype()`. When this method is
  invoked with a PSR-7 response, the following occurs:
  - That response is injected in `Next` and `Dispatch` instances, to allow
    dispatching legacy callable middleware as if it were http-interop
    middleware.
  - Any callable middleware implementing the legacy signature will now be
    decorated using the above `CallableMiddlewareWrapper` in order to adapt it
    as http-interop middleware.

- [#78](https://github.com/zendframework/zend-stratigility/pull/78) adds a new
  method to each of `Zend\Stratigility\MiddlewarePipe`, `Next`, and `Dispatch`:
  `raiseThrowables()`. When called, `Dispatch` will no longer wrap dispatch of
  middleware in a try/catch block, allowing throwables/exceptions to bubble out.
  This enables the ability to create error handling middleware as an outer layer
  or your application instead of relying on error middleware and/or the final
  handler. Typical usage will be to call the method on the `MiddlewarePipe`
  before dispatching it.

### Changed

- [#70](https://github.com/zendframework/zend-stratigility/pull/70) makes the
  following changes to `Zend\Stratigility\FinalHandler`:

  - It now pulls the original request using the `originalRequest` attribute,
    instead of `getOriginalRequest()`; see the deprecation of
    `Zend\Stratigility\Http\Request`, below, for why this works.
  - It no longer writes to the response using the
    `Zend\Stratigility\Http\Response`-specific `write()` method, but rather
    pulls the message body and writes to that.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) updates
  `MiddlewarePipe` to inject the `$response` argument to `__invoke()` as the
  response prototype.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) updates
  `Zend\Stratigility\Next` to implement the http-interop middleware
  `DelegateInterface`. It also updates `Zend\Stratigility\Dispatch` to add a new
  method, `process()`, following the `DelegateInterface` signature, thus
  allowing `Next` to properly process http-interop middleware. These methods
  will use the composed response prototype, if present, to invoke callable
  middleware using the legacy signature.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) updates
  `Next` to allow the `$done` constructor argument to be an http-interop
  `DelegateInterface`, and will invoke it as such if the queue is exhausted.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) updates
  `Route` (which is used internally by `MiddlewarePipe` to allow either callable
  or http-interop middleware as route handlers.

### Deprecated

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) deprecates
  the `Zend\Stratigility\FinalHandler` class. We now recommend using the
  `NoopFinalHandler`, along with the `ErrorHandler` and `NotFoundHandler`
  middleware (or equivalents) to provide a more fine-grained, flexible, error
  handling solution for your applications.

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) deprecates
  the `Zend\Stratigility\Dispatch` class. This class is used internally by
  `Next`, and deprecation should not affect the majority of users.

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) deprecates
  `Zend\Stratigility\ErrorMiddlewareInterface`. We recommend instead using
  exceptions, along with the `ErrorHandler`, to provide error handling for your
  application.

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) updates
  `Zend\Stratigility\MiddlewarePipe::__invoke()` to emit a deprecation notice if
  no `$out` argument is provided, as version 2 will require it.

- [#66](https://github.com/zendframework/zend-stratigility/pull/66) updates
  `Zend\Stratigility\Next::__invoke()` to emit a deprecation notice if
  a non-null `$err` argument is provided; middleware should raise an exception,
  instead of invoking middleware implementing `ErrorMiddlewareInterface`.

- [#70](https://github.com/zendframework/zend-stratigility/pull/70) deprecates
  `Zend\Stratigility\Http\Request`. Additionally:

  - The composed "PSR Request" is now injected with an additional attribute,
    `originalRequest`, allowing retrieval using standard PSR-7 attribute access.
  - The methods `getCurrentRequest()` and `getOriginalRequest()` now emit
    deprecation notices when invoked, urging users to update their code.

- [#70](https://github.com/zendframework/zend-stratigility/pull/70) deprecates
  `Zend\Stratigility\Http\ResponseInterface`.

- [#70](https://github.com/zendframework/zend-stratigility/pull/70) deprecates
  `Zend\Stratigility\Http\Response`. Additionally, the methods `write()`,
  `end()`, `isComplete()`, and `getOriginalResponse()` now emit deprecation
  notices when invoked, urging users to update their code.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) deprecates
  the `$response` argument in existing callable middleware. Please only operate
  on the response returned by `$next`/`$delegate`, or create a response. See the
  documentation [section on response arguments](https://docs.zendframework.com/zend-stratigility/api/#response-argument)
  for more details.

- [#75](https://github.com/zendframework/zend-stratigility/pull/75) deprecates
  usage of error middleware, and thus deprecates the `$err` argument to `$next`;
  explicitly invoking error middleware using that argument to `$next` will now
  raise a deprecation notice.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.2 - TBD

### Added

- [#58](https://github.com/zendframework/zend-stratigility/pull/58) updates the
  documentation to use mkdocs for generation, and pushes the documentation to
  https://zendframework.github.io/zend-stratigility/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2016-03-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#52](https://github.com/zendframework/zend-stratigility/pull/52) fixes the
  behavior of the `FinalHandler` with regards to exception handling, ensuring
  that the reason phrase reported corresponds to the HTTP status code used.
- [#54](https://github.com/zendframework/zend-stratigility/pull/54) modifies the
  behavior of the `FinalHandler` when creating an error or 404 response to call
  `write()` instead of `end()` on the response object. This fixes a lingering
  issue with emitting the `Content-Length` header from the `SapiEmitter`, as
  well as prevents the `SapiEmitter` from raising exceptions when doing so
  (which was happening starting with 1.2.0).

## 1.2.0 - 2016-03-17

This release contains two potential backwards compatibility breaks:

- In versions prior to 1.2.0, after `Zend\Stratigility\Http\Response::end()` was
  called, `with*()` operations were performed as no-ops, which led to
  hard-to-detect errors. Starting with 1.2.0, they now raise a
  `RuntimeException`.

- In versions prior to 1.2.0, `Zend\Stratigility\FinalHandler` always provided
  exception details in the response payload for errors. Starting with 1.2.0, it
  only does so if not in a production environment (which is the default
  environment).

### Added

- [#36](https://github.com/zendframework/zend-stratigility/pull/36) adds a new
  `InvalidMiddlewareException`, with the static factory `fromValue()` that
  provides an exception message detailing the invalid type. `MiddlewarePipe` now
  throws this exception from the `pipe()` method when a non-callable value is
  provided.
- [#46](https://github.com/zendframework/zend-stratigility/pull/46) adds
  `FinalHandler::setOriginalResponse()`, allowing you to alter the response used
  for comparisons when the `FinalHandler` is invoked.
- [#37](https://github.com/zendframework/zend-stratigility/pull/37) and
  [#49](https://github.com/zendframework/zend-stratigility/pull/49) add
  support in `Zend\Stratigility\Dispatch` to catch PHP 7 `Throwable`s.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#30](https://github.com/zendframework/zend-stratigility/pull/30) updates the
  `Response` implementation to raise exceptions from `with*()` methods if they
  are called after `end()`.
- [#46](https://github.com/zendframework/zend-stratigility/pull/46) fixes the
  behavior of `FinalHandler::handleError()` to only display exception details
  when not in production environments, and changes the default environment to
  production.

## 1.1.3 - 2016-03-17

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#39](https://github.com/zendframework/zend-stratigility/pull/39) updates the
  FinalHandler to ensure that emitted exception messages include previous
  exceptions.

## 1.1.2 - 2015-10-09

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#32](https://github.com/zendframework/zend-stratigility/pull/32) updates the
  request and response typehints in `Zend\Stratigility\Dispatch` to use the
  corresponding PSR-7 interfaces, instead of the Stratigility-specific
  decorators. This fixes issues when calling `$next()` with non-Stratigility
  instances of either.

## 1.1.1 - 2015-08-25

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#25](https://github.com/zendframework/zend-stratigility/pull/25) modifies the
  constructor of `Next` to clone the incoming `SplQueue` instance, ensuring the
  original can be re-used for subsequent invocations (e.g., within an async
  listener environment such as React).

## 1.1.0 - 2015-06-25

### Added

- [#13](https://github.com/zendframework/zend-stratigility/pull/13) adds
  `Utils::getStatusCode($error, ResponseInterface $response)`; this static
  method will attempt to use an exception code as an HTTP status code, if it
  falls in a valid HTTP error status range. If the error is not an exception, it
  ensures that the status code is an error status.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#12](https://github.com/zendframework/zend-stratigility/pull/12) updates
  `FinalHandler` such that it will return the response provided at invocation
  if it differs from the response at initialization (i.e., a new response
  instance, or if the body size has changed). This allows you to safely call
  `$next()` from all middleware in order to allow post-processing.

## 1.0.2 - 2015-06-24

### Added

- [#14](https://github.com/zendframework/zend-stratigility/pull/14) adds
  [bookdown](http://bookdown.io) documentation.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.1 - 2015-06-16

### Added

- [#8](https://github.com/zendframework/zend-stratigility/pull/8) adds a
  `phpcs.xml` PHPCS configuration file, allowing execution of each of `phpcs`
  and `phpcbf` without arguments.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#7](https://github.com/zendframework/zend-stratigility/pull/7) ensures that
  arity checks on PHP callables in array format (`[$instance, $method]`,
  `['ClassName', 'method']`) work, as well as on static methods using the string
  syntax (`'ClassName::method'`). This allows them to be used without issue as
  middleware handlers.

## 1.0.0 - 2015-05-14

First stable release, and first relase as `zend-stratigility`.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
