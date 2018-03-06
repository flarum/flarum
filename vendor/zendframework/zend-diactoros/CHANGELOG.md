# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.7.1 - 2018-02-26

### Added

- Nothing.

### Changed

- [#293](https://github.com/zendframework/zend-diactoros/pull/293) updates
  `Uri::getHost()` to cast the value via `strtolower()` before returning it.
  While this represents a change, it is fixing a bug in our implementation: 
  the PSR-7 specification for the method, which follows IETF RFC 3986 section
  3.2.2, requires that the host name be normalized to lowercase.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#290](https://github.com/zendframework/zend-diactoros/pull/290) fixes
  `Stream::getSize()` such that it checks that the result of `fstat` was
  succesful before attempting to return its `size` member; in the case of an
  error, it now returns `null`.

## 1.7.0 - 2018-01-04

### Added

- [#285](https://github.com/zendframework/zend-diactoros/pull/285) adds a new
  custom response type, `Zend\Diactoros\Response\XmlResponse`, for generating
  responses representing XML. Usage is the same as with the `HtmlResponse` or
  `TextResponse`; the response generated will have a `Content-Type:
  application/xml` header by default.

- [#280](https://github.com/zendframework/zend-diactoros/pull/280) adds the
  response status code/phrase pairing "103 Early Hints" to the
  `Response::$phrases` property. This is a new status proposed via
  [RFC 8297](https://datatracker.ietf.org/doc/rfc8297/).

- [#279](https://github.com/zendframework/zend-diactoros/pull/279) adds explicit
  support for PHP 7.2; previously, we'd allowed build failures, though none
  occured; we now require PHP 7.2 builds to pass.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.6.1 - 2017-10-12

### Added

- Nothing.

### Changed

- [#273](https://github.com/zendframework/zend-diactoros/pull/273) updates each
  of the SAPI emitter implementations to emit the status line after emitting
  other headers; this is done to ensure that the status line is not overridden
  by PHP.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#273](https://github.com/zendframework/zend-diactoros/pull/273) modifies how
  the `SapiEmitterTrait` calls `header()` to ensure that a response code is
  _always_ passed as the third argument; this is done to prevent PHP from
  silently overriding it.

## 1.6.0 - 2017-09-13

### Added

- Nothing.

### Changed

- [#270](https://github.com/zendframework/zend-diactoros/pull/270) changes the
  behavior of `Zend\Diactoros\Server`: it no longer creates an output buffer.

- [#270](https://github.com/zendframework/zend-diactoros/pull/270) changes the
  behavior of the two SAPI emitters in two backwards-incompatible ways:

  - They no longer auto-inject a `Content-Length` header. If you need this
    functionality, zendframework/zend-expressive-helpers 4.1+ provides it via
    `Zend\Expressive\Helper\ContentLengthMiddleware`.

  - They no longer flush the output buffer. Instead, if headers have been sent,
    or the output buffer exists and has a non-zero length, the emitters raise an
    exception, as mixed PSR-7/output buffer content creates a blocking issue.
    If you are emitting content via `echo`, `print`, `var_dump`, etc., or not
    catching PHP errors or exceptions, you will need to either fix your
    application to always work with a PSR-7 response, or provide your own
    emitters that allow mixed output mechanisms.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.5.0 - 2017-08-22

### Added

- [#205](https://github.com/zendframework/zend-diactoros/pull/205) adds support
  for PHP 7.2.

- [#250](https://github.com/zendframework/zend-diactoros/pull/250) adds a new
  API to `JsonResponse` to avoid the need for decoding the response body in
  order to make changes to the underlying content. New methods include:
  - `getPayload()`: retrieve the unencoded payload.
  - `withPayload($data)`: create a new instance with the given data.
  - `getEncodingOptions()`: retrieve the flags to use when encoding the payload
    to JSON.
  - `withEncodingOptions(int $encodingOptions)`: create a new instance that uses
    the provided flags when encoding the payload to JSON.

### Changed

- [#249](https://github.com/zendframework/zend-diactoros/pull/249) changes the
  behavior of the various `Uri::with*()` methods slightly: if the value
  represents no change, these methods will return the same instance instead of a
  new one.

- [#248](https://github.com/zendframework/zend-diactoros/pull/248) changes the
  behavior of `Uri::getUserInfo()` slightly: it now (correctly) returns the
  percent-encoded values for the user and/or password, per RFC 3986 Section
  3.2.1. `withUserInfo()` will percent-encode values, using a mechanism that
  prevents double-encoding.

- [#243](https://github.com/zendframework/zend-diactoros/pull/243) changes the
  exception messages thrown by `UploadedFile::getStream()` and `moveTo()` when
  an upload error exists to include details about the upload error.

- [#233](https://github.com/zendframework/zend-diactoros/pull/233) adds a new
  argument to `SapiStreamEmitter::emit`, `$maxBufferLevel` **between** the
  `$response` and `$maxBufferLength` arguments. This was done because the
  `Server::listen()` method passes only the response and `$maxBufferLevel` to
  emitters; previously, this often meant that streams were being chunked 2 bytes
  at a time versus the expected default of 8kb.

  If you were calling the `SapiStreamEmitter::emit()` method manually
  previously, you will need to update your code.

### Deprecated

- Nothing.

### Removed

- [#205](https://github.com/zendframework/zend-diactoros/pull/205) and
  [#243](https://github.com/zendframework/zend-diactoros/pull/243) **remove
  support for PHP versions prior to 5.6 as well as HHVM**.

### Fixed

- [#248](https://github.com/zendframework/zend-diactoros/pull/248) fixes how the
  `Uri` class provides user-info within the URI authority; the value is now
  correctly percent-encoded , per RFC 3986 Section 3.2.1.

## 1.4.1 - 2017-08-17

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#260](https://github.com/zendframework/zend-diactoros/pull/260) removes
  support for HHVM, as tests have failed against it for some time.

### Fixed

- [#247](https://github.com/zendframework/zend-diactoros/pull/247) fixes the
  `Stream` and `RelativeStream` `__toString()` method implementations to check
  if the stream `isSeekable()` before attempting to `rewind()` it, ensuring that
  the method does not raise exceptions (PHP does not allow exceptions in that
  method). In particular, this fixes an issue when using AWS S3 streams.

- [#252](https://github.com/zendframework/zend-diactoros/pull/252) provides a
  fix to the `SapiEmitterTrait` to ensure that any `Set-Cookie` headers in the
  response instance do not override those set by PHP when a session is created
  and/or regenerated.

- [#257](https://github.com/zendframework/zend-diactoros/pull/257) provides a
  fix for the `PhpInputStream::read()` method to ensure string content that
  evaluates as empty (including `0`) is still cached.

- [#258](https://github.com/zendframework/zend-diactoros/pull/258) updates the
  `Uri::filterPath()` method to allow parens within a URI path, per [RFC 3986
  section 3.3](https://tools.ietf.org/html/rfc3986#section-3.3) (parens are
  within the character set "sub-delims").

## 1.4.0 - 2017-04-06

### Added

- [#219](https://github.com/zendframework/zend-diactoros/pull/219) adds two new
  classes, `Zend\Diactoros\Request\ArraySerializer` and
  `Zend\Diactoros\Response\ArraySerializer`. Each exposes the static methods
  `toArray()` and `fromArray()`, allowing de/serialization of messages from and
  to arrays.

- [#236](https://github.com/zendframework/zend-diactoros/pull/236) adds two new
  constants to the `Response` class: `MIN_STATUS_CODE_VALUE` and
  `MAX_STATUS_CODE_VALUE`.

### Changes

- [#240](https://github.com/zendframework/zend-diactoros/pull/240) changes the
  behavior of `ServerRequestFactory::fromGlobals()` when no `$cookies` argument
  is present. Previously, it would use `$_COOKIES`; now, if a `Cookie` header is
  present, it will parse and use that to populate the instance instead.

  This change allows utilizing cookies that contain period characters (`.`) in
  their names (PHP's built-in cookie handling renames these to replace `.` with
  `_`, which can lead to synchronization issues with clients).

- [#235](https://github.com/zendframework/zend-diactoros/pull/235) changes the
  behavior of `Uri::__toString()` to better follow proscribed behavior in PSR-7.
  In particular, prior to this release, if a scheme was missing but an authority
  was present, the class was incorrectly returning a value that did not include
  a `//` prefix. As of this release, it now does this correctly.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.11 - 2017-04-06

### Added

- Nothing.

### Changes

- [#241](https://github.com/zendframework/zend-diactoros/pull/241) changes the
  constraint by which the package provides `psr/http-message-implementation` to
  simply `1.0` instead of `~1.0.0`, to follow how other implementations provide
  PSR-7.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#161](https://github.com/zendframework/zend-diactoros/pull/161) adds
  additional validations to header names and values to ensure no malformed values
  are provided.

- [#234](https://github.com/zendframework/zend-diactoros/pull/234) fixes a
  number of reason phrases in the `Response` instance, and adds automation from
  the canonical IANA sources to ensure any new phrases added are correct.

## 1.3.10 - 2017-01-23

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#226](https://github.com/zendframework/zend-diactoros/pull/226) fixed an
  issue with the `SapiStreamEmitter` causing the response body to be cast
  to `(string)` and also be read as a readable stream, potentially producing
  double output.

## 1.3.9 - 2017-01-17

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#223](https://github.com/zendframework/zend-diactoros/issues/223)
  [#224](https://github.com/zendframework/zend-diactoros/pull/224) fixed an issue
  with the `SapiStreamEmitter` consuming too much memory when producing output
  for readable bodies.

## 1.3.8 - 2017-01-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#222](https://github.com/zendframework/zend-diactoros/pull/222) fixes the
  `SapiStreamEmitter`'s handling of the `Content-Range` header to properly only
  emit a range of bytes if the header value is in the form `bytes {first-last}/length`.
  This allows using other range units, such as `items`, without incorrectly
  emitting truncated content.

## 1.3.7 - 2016-10-11

### Added

- [#208](https://github.com/zendframework/zend-diactoros/pull/208) adds several
  missing response codes to `Zend\Diactoros\Response`, including:
  - 226 ('IM used')
  - 308 ('Permanent Redirect')
  - 444 ('Connection Closed Without Response')
  - 499 ('Client Closed Request')
  - 510 ('Not Extended')
  - 599 ('Network Connect Timeout Error')
- [#211](https://github.com/zendframework/zend-diactoros/pull/211) adds support
  for UTF-8 characters in query strings handled by `Zend\Diactoros\Uri`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.6 - 2016-09-07

### Added

- [#170](https://github.com/zendframework/zend-diactoros/pull/170) prepared
  documentation for publication at https://zendframework.github.io/zend-diactoros/
- [#165](https://github.com/zendframework/zend-diactoros/pull/165) adds support
  for Apache `REDIRECT_HTTP_*` header detection in the `ServerRequestFactory`.
- [#166](https://github.com/zendframework/zend-diactoros/pull/166) adds support
  for UTF-8 characters in URI paths.
- [#204](https://github.com/zendframework/zend-diactoros/pull/204) adds testing
  against PHP 7.1 release-candidate builds.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#186](https://github.com/zendframework/zend-diactoros/pull/186) fixes a typo
  in a variable name within the `SapiStreamEmitter`.
- [#200](https://github.com/zendframework/zend-diactoros/pull/200) updates the
  `SapiStreamEmitter` to implement a check for `isSeekable()` prior to attempts
  to rewind; this allows it to work with non-seekable streams such as the
  `CallbackStream`.
- [#169](https://github.com/zendframework/zend-diactoros/pull/169) ensures that
  response serialization always provides a `\r\n\r\n` sequence following the
  headers, even when no message body is present, to ensure it conforms with RFC
  7230.
- [#175](https://github.com/zendframework/zend-diactoros/pull/175) updates the
  `Request` class to set the `Host` header from the URI host if no header is
  already present. (Ensures conformity with PSR-7 specification.)
- [#197](https://github.com/zendframework/zend-diactoros/pull/197) updates the
  `Uri` class to ensure that string serialization does not include a colon after
  the host name if no port is present in the instance.

## 1.3.5 - 2016-03-17

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#160](https://github.com/zendframework/zend-diactoros/pull/160) fixes HTTP
  protocol detection in the `ServerRequestFactory` to work correctly with HTTP/2.

## 1.3.4 - 2016-03-17

### Added

- [#119](https://github.com/zendframework/zend-diactoros/pull/119) adds the 451
  (Unavailable for Legal Reasons) status code to the `Response` class.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#117](https://github.com/zendframework/zend-diactoros/pull/117) provides
  validation of the HTTP protocol version.
- [#127](https://github.com/zendframework/zend-diactoros/pull/127) now properly
  removes attributes with `null` values when calling `withoutAttribute()`.
- [#132](https://github.com/zendframework/zend-diactoros/pull/132) updates the
  `ServerRequestFactory` to marshal the request path fragment, if present.
- [#142](https://github.com/zendframework/zend-diactoros/pull/142) updates the
  exceptions thrown by `HeaderSecurity` to include the header name and/or
  value.
- [#148](https://github.com/zendframework/zend-diactoros/pull/148) fixes several
  stream operations to ensure they raise exceptions when the internal pointer
  is at an invalid position.
- [#151](https://github.com/zendframework/zend-diactoros/pull/151) ensures
  URI fragments are properly encoded.

## 1.3.3 - 2016-01-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#135](https://github.com/zendframework/zend-diactoros/pull/135) fixes the
  behavior of `ServerRequestFactory::marshalHeaders()` to no longer omit
  `Cookie` headers from the aggregated headers. While the values are parsed and
  injected into the cookie params, it's useful to have access to the raw headers
  as well.

## 1.3.2 - 2015-12-22

### Added

- [#124](https://github.com/zendframework/zend-diactoros/pull/124) adds four
  more optional arguments to the `ServerRequest` constructor:
  - `array $cookies`
  - `array $queryParams`
  - `null|array|object $parsedBody`
  - `string $protocolVersion`
  `ServerRequestFactory` was updated to pass values for each of these parameters
  when creating an instance, instead of using the related `with*()` methods on
  an instance.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#122](https://github.com/zendframework/zend-diactoros/pull/122) updates the
  `ServerRequestFactory` to retrieve the HTTP protocol version and inject it in
  the generated `ServerRequest`, which previously was not performed.

## 1.3.1 - 2015-12-16

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#113](https://github.com/zendframework/zend-diactoros/pull/113) fixes an
  issue in the response serializer, ensuring that the status code in the
  deserialized response is an integer.
- [#115](https://github.com/zendframework/zend-diactoros/pull/115) fixes an
  issue in the various text-basd response types (`TextResponse`, `HtmlResponse`,
  and `JsonResponse`); due to the fact that the constructor was not
  rewinding the message body stream, `getContents()` was thus returning `null`,
  as the pointer was at the end of the stream. The constructor now rewinds the
  stream after populating it in the constructor.

## 1.3.0 - 2015-12-15

### Added

- [#110](https://github.com/zendframework/zend-diactoros/pull/110) adds
  `Zend\Diactoros\Response\SapiEmitterTrait`, which provides the following
  private method definitions:
  - `injectContentLength()`
  - `emitStatusLine()`
  - `emitHeaders()`
  - `flush()`
  - `filterHeader()`
  The `SapiEmitter` implementation has been updated to remove those methods and
  instead compose the trait.
- [#111](https://github.com/zendframework/zend-diactoros/pull/111) adds
  a new emitter implementation, `SapiStreamEmitter`; this emitter type will
  loop through the stream instead of emitting it in one go, and supports content
  ranges.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.1 - 2015-12-15

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#101](https://github.com/zendframework/zend-diactoros/pull/101) fixes the
  `withHeader()` implementation to ensure that if the header existed previously
  but using a different casing strategy, the previous version will be removed
  in the cloned instance.
- [#103](https://github.com/zendframework/zend-diactoros/pull/103) fixes the
  constructor of `Response` to ensure that null status codes are not possible.
- [#99](https://github.com/zendframework/zend-diactoros/pull/99) fixes
  validation of header values submitted via request and response constructors as
  follows:
  - numeric (integer and float) values are now properly allowed (this solves
    some reported issues with setting Content-Length headers)
  - invalid header names (non-string values or empty strings) now raise an
    exception.
  - invalid individual header values (non-string, non-numeric) now raise an
    exception.

## 1.2.0 - 2015-11-24

### Added

- [#88](https://github.com/zendframework/zend-diactoros/pull/88) updates the
  `SapiEmitter` to emit a `Content-Length` header with the content length as
  reported by the response body stream, assuming that
  `StreamInterface::getSize()` returns an integer.
- [#77](https://github.com/zendframework/zend-diactoros/pull/77) adds a new
  response type, `Zend\Diactoros\Response\TextResponse`, for returning plain
  text responses. By default, it sets the content type to `text/plain;
  charset=utf-8`; per the other response types, the signature is `new
  TextResponse($text, $status = 200, array $headers = [])`.
- [#90](https://github.com/zendframework/zend-diactoros/pull/90) adds a new
  `Zend\Diactoros\CallbackStream`, allowing you to back a stream with a PHP
  callable (such as a generator) to generate the message content. Its
  constructor accepts the callable: `$stream = new CallbackStream($callable);`

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#77](https://github.com/zendframework/zend-diactoros/pull/77) updates the
  `HtmlResponse` to set the charset to utf-8 by default (if no content type
  header is provided at instantiation).

## 1.1.4 - 2015-10-16

### Added

- [#98](https://github.com/zendframework/zend-diactoros/pull/98) adds
  `JSON_UNESCAPED_SLASHES` to the default `json_encode` flags used by
  `Zend\Diactoros\Response\JsonResponse`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#96](https://github.com/zendframework/zend-diactoros/pull/96) updates
  `withPort()` to allow `null` port values (indicating usage of default for
  the given scheme).
- [#91](https://github.com/zendframework/zend-diactoros/pull/91) fixes the
  logic of `withUri()` to do a case-insensitive check for an existing `Host`
  header, replacing it with the new one.

## 1.1.3 - 2015-08-10

### Added

- [#73](https://github.com/zendframework/zend-diactoros/pull/73) adds caching of
  the vendor directory to the Travis-CI configuration, to speed up builds.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#71](https://github.com/zendframework/zend-diactoros/pull/71) fixes the
  docblock of the `JsonResponse` constructor to typehint the `$data` argument
  as `mixed`.
- [#73](https://github.com/zendframework/zend-diactoros/pull/73) changes the
  behavior in `Request` such that if it marshals a stream during instantiation,
  the stream is marked as writeable (specifically, mode `wb+`).
- [#85](https://github.com/zendframework/zend-diactoros/pull/85) updates the
  behavior of `Zend\Diactoros\Uri`'s various `with*()` methods that are
  documented as accepting strings to raise exceptions on non-string input.
  Previously, several simply passed non-string input on verbatim, others
  normalized the input, and a few correctly raised the exceptions. Behavior is
  now consistent across each.
- [#87](https://github.com/zendframework/zend-diactoros/pull/87) fixes
  `UploadedFile` to ensure that `moveTo()` works correctly in non-SAPI
  environments when the file provided to the constructor is a path.

## 1.1.2 - 2015-07-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#67](https://github.com/zendframework/zend-diactoros/pull/67) ensures that
  the `Stream` class only accepts `stream` resources, not any resource.

## 1.1.1 - 2015-06-25

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#64](https://github.com/zendframework/zend-diactoros/pull/64) fixes the
  behavior of `JsonResponse` with regards to serialization of `null` and scalar
  values; the new behavior is to serialize them verbatim, without any casting.

## 1.1.0 - 2015-06-24

### Added

- [#52](https://github.com/zendframework/zend-diactoros/pull/52),
  [#58](https://github.com/zendframework/zend-diactoros/pull/58),
  [#59](https://github.com/zendframework/zend-diactoros/pull/59), and
  [#61](https://github.com/zendframework/zend-diactoros/pull/61) create several
  custom response types for simplifying response creation:

  - `Zend\Diactoros\Response\HtmlResponse` accepts HTML content via its
    constructor, and sets the `Content-Type` to `text/html`.
  - `Zend\Diactoros\Response\JsonResponse` accepts data to serialize to JSON via
    its constructor, and sets the `Content-Type` to `application/json`.
  - `Zend\Diactoros\Response\EmptyResponse` allows creating empty, read-only
    responses, with a default status code of 204.
  - `Zend\Diactoros\Response\RedirectResponse` allows specifying a URI for the
    `Location` header in the constructor, with a default status code of 302.

  Each also accepts an optional status code, and optional headers (which can
  also be used to provide an alternate `Content-Type` in the case of the HTML
  and JSON responses).

### Deprecated

- Nothing.

### Removed

- [#43](https://github.com/zendframework/zend-diactoros/pull/43) removed both
  `ServerRequestFactory::marshalUri()` and `ServerRequestFactory::marshalHostAndPort()`,
  which were deprecated prior to the 1.0 release.

### Fixed

- [#29](https://github.com/zendframework/zend-diactoros/pull/29) fixes request
  method validation to allow any valid token as defined by [RFC
  7230](http://tools.ietf.org/html/rfc7230#appendix-B). This allows usage of
  custom request methods, vs a static, hard-coded list.

## 1.0.5 - 2015-06-24

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#60](https://github.com/zendframework/zend-diactoros/pull/60) fixes
  the behavior of `UploadedFile` when the `$errorStatus` provided at
  instantiation is not `UPLOAD_ERR_OK`. Prior to the fix, an
  `InvalidArgumentException` would occur at instantiation due to the fact that
  the upload file was missing or invalid. With the fix, no exception is raised
  until a call to `moveTo()` or `getStream()` is made.

## 1.0.4 - 2015-06-23

This is a security release.

A patch has been applied to `Zend\Diactoros\Uri::filterPath()` that ensures that
paths can only begin with a single leading slash. This prevents the following
potential security issues:

- XSS vectors. If the URI path is used for links or form targets, this prevents
  cases where the first segment of the path resembles a domain name, thus
  creating scheme-relative links such as `//example.com/foo`. With the patch,
  the leading double slash is reduced to a single slash, preventing the XSS
  vector.
- Open redirects. If the URI path is used for `Location` or `Link` headers,
  without a scheme and authority, potential for open redirects exist if clients
  do not prepend the scheme and authority. Again, preventing a double slash
  corrects the vector.

If you are using `Zend\Diactoros\Uri` for creating links, form targets, or
redirect paths, and only using the path segment, we recommend upgrading
immediately.

### Added

- [#25](https://github.com/zendframework/zend-diactoros/pull/25) adds
  documentation. Documentation is written in markdown, and can be converted to
  HTML using [bookdown](http://bookdown.io). New features now MUST include
  documentation for acceptance.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#51](https://github.com/zendframework/zend-diactoros/pull/51) fixes
  `MessageTrait::getHeaderLine()` to return an empty string instead of `null` if
  the header is undefined (which is the behavior specified in PSR-7).
- [#57](https://github.com/zendframework/zend-diactoros/pull/57) fixes the
  behavior of how the `ServerRequestFactory` marshals upload files when they are
  represented as a nested associative array.
- [#49](https://github.com/zendframework/zend-diactoros/pull/49) provides several
  fixes that ensure that Diactoros complies with the PSR-7 specification:
  - `MessageInterface::getHeaderLine()` MUST return a string (that string CAN be
    empty). Previously, Diactoros would return `null`.
  - If no `Host` header is set, the `$preserveHost` flag MUST be ignored when
    calling `withUri()` (previously, Diactoros would not set the `Host` header
    if `$preserveHost` was `true`, but no `Host` header was present).
  - The request method MUST be a string; it CAN be empty. Previously, Diactoros
    would return `null`.
  - The request MUST return a `UriInterface` instance from `getUri()`; that
    instance CAN be empty. Previously, Diactoros would return `null`; now it
    lazy-instantiates an empty `Uri` instance on initialization.
- [ZF2015-05](http://framework.zend.com/security/advisory/ZF2015-05) was
  addressed by altering `Uri::filterPath()` to prevent emitting a path prepended
  with multiple slashes.

## 1.0.3 - 2015-06-04

### Added

- [#48](https://github.com/zendframework/zend-diactoros/pull/48) drops the
  minimum supported PHP version to 5.4, to allow an easier upgrade path for
  Symfony 2.7 users, and potential Drupal 8 usage.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.2 - 2015-06-04

### Added

- [#27](https://github.com/zendframework/zend-diactoros/pull/27) adds phonetic
  pronunciation of "Diactoros" to the README file.
- [#36](https://github.com/zendframework/zend-diactoros/pull/36) adds property
  annotations to the class-level docblock of `Zend\Diactoros\RequestTrait` to
  ensure properties inherited from the `MessageTrait` are inherited by
  implementations.

### Deprecated

- Nothing.

### Removed

- Nothing.
-
### Fixed

- [#41](https://github.com/zendframework/zend-diactoros/pull/41) fixes the
  namespace for test files to begin with `ZendTest` instead of `Zend`.
- [#46](https://github.com/zendframework/zend-diactoros/pull/46) ensures that
  the cookie and query params for the `ServerRequest` implementation are
  initialized as arrays.
- [#47](https://github.com/zendframework/zend-diactoros/pull/47) modifies the
  internal logic in `HeaderSecurity::isValid()` to use a regular expression
  instead of character-by-character comparisons, improving performance.

## 1.0.1 - 2015-05-26

### Added

- [#10](https://github.com/zendframework/zend-diactoros/pull/10) adds
  `Zend\Diactoros\RelativeStream`, which will return stream contents relative to
  a given offset (i.e., a subset of the stream).  `AbstractSerializer` was
  updated to create a `RelativeStream` when creating the body of a message,
  which will prevent duplication of the stream in-memory.
- [#21](https://github.com/zendframework/zend-diactoros/pull/21) adds a
  `.gitattributes` file that excludes directories and files not needed for
  production; this will further minify the package for production use cases.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-diactoros/pull/9) ensures that
  attributes are initialized to an empty array, ensuring that attempts to
  retrieve single attributes when none are defined will not produce errors.
- [#14](https://github.com/zendframework/zend-diactoros/pull/14) updates
  `Zend\Diactoros\Request` to use a `php://temp` stream by default instead of
  `php://memory`, to ensure requests do not create an out-of-memory condition.
- [#15](https://github.com/zendframework/zend-diactoros/pull/15) updates
  `Zend\Diactoros\Stream` to ensure that write operations trigger an exception
  if the stream is not writeable. Additionally, it adds more robust logic for
  determining if a stream is writeable.

## 1.0.0 - 2015-05-21

First stable release, and first release as `zend-diactoros`.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
