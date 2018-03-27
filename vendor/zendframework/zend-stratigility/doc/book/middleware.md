# Middleware

What is middleware?

Middleware is code that exists between the request and response, and which can
take the incoming request, perform actions based on it, and either complete the
response or pass delegation on to the next middleware in the queue.

```php
use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Server;

require __DIR__ . '/../vendor/autoload.php';

$app    = new MiddlewarePipe();
$server = Server::createServer($app, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

// Landing page
$app->pipe('/', function ($req, $res, $next) {
    if (! in_array($req->getUri()->getPath(), ['/', ''], true)) {
        return $next($req, $res);
    }
    $res->getBody()->write('Hello world!');
    return $res;
});

// Another page
$app->pipe('/foo', function ($req, $res, $next) {
    $res->getBody()->write('FOO!');
    return $res;
});

$server->listen();
```

In the above example, we have two examples of middleware. The first is a
landing page, and listens at the root path. If the request path is empty or
`/`, it completes the response. If it is not, it delegates to the next
middleware in the stack. The second middleware matches on the path `/foo`
&mdash; meaning it will match `/foo`, `/foo/`, and any path beneath. In that
case, it will complete the response with its own message. If no paths match at
this point, a "final handler" is composed by default to report 404 status.

So, concisely put, _middleware are PHP callables that accept a request and
response object, and do something with it_.

> ### http-interop middleware
>
> The above example demonstrates the legacy (pre-1.3.0) signature for
> middleware, which is also widely used across other middleware frameworks
> such as Slim, Relay, Adroit, etc.
>
> http-interop is a project attempting to standardize middleware signatures.
> The signature it uses for server-side middleware is:
>
> ```php
> namespace Interop\Http\Middleware;
>
> use Psr\Http\Message\ResponseInterface;
> use Psr\Http\Message\ServerRequestInterface;
>
> interface ServerMiddlewareInterface
> {
>     public function process(
>         ServerRequestInterface $request,
>         DelegateInterface $delegate
>     ) : ResponseInterface;
> }
> ```
>
> where `DelegateInterface` is defined as:
>
> ```php
> namespace Interop\Http\Middleware;
>
> use Psr\Http\Message\RequestInterface;
> use Psr\Http\Message\ResponseInterface;
>
> interface DelegateInterface
> {
>     public function process(
>         RequestInterface $request
>     ) : ResponseInterface;
> }
> ```
>
> Stratigility allows you to implement `ServerMiddlewareInterface` to provide
> middleware.  Additionally, you can define `callable` middleware with the
> following signature, and it will be dispatched as http-interop middleware:
>
> ```php
> function(
>     ServerRequestInterface $request,
>     DelegateInterface $delegate
> ) : ResponseInterface;
> ```
>
> (The `$request` argument does not require a typehint when defining callable
> middleware, but we encourage its use.)
>
> As such, the above example can also be written as follows:
>
> ```php
> use Interop\Http\Middleware\DelegateInterface;
> use Zend\Diactoros\Response\TextResponse;
>
> $app->pipe('/', function ($req, DelegateInterface $delegate) {
>     if (! in_array($req->getUri()->getPath(), ['/', ''], true)) {
>         return $delegate->process($req);
>     }
>     return new TextResponse('Hello world!');
> });
> ```

Middleware can decide more processing can be performed by calling the `$next`
callable (or, when defining http-interop middleware, `$delegate`) passed during
invocation. With this paradigm, you can build a workflow engine for handling
requests &mdash; for instance, you could have middleware perform the following:

- Handle authentication details
- Perform content negotiation
- Perform HTTP negotiation
- Route the path to a more appropriate, specific handler

Each middleware can itself be middleware, and can attach to specific paths,
allowing you to mix and match applications under a common domain. As an
example, you could put API middleware next to middleware that serves its
documentation, next to middleware that serves files, and segregate each by URI:

```php
$app->pipe('/api', $apiMiddleware);
$app->pipe('/docs', $apiDocMiddleware);
$app->pipe('/files', $filesMiddleware);
```

The handlers in each middleware attached this way will see a URI with that path
segment stripped, allowing them to be developed separately and re-used under
any path you wish.

Within Stratigility, middleware can be:

- Any PHP callable that accepts, minimally, a
  [PSR-7](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md)
  ServerRequest and Response (in that order), and, optionally, a callable (for
  invoking the next middleware in the queue, if any).
- Any [http-interop 0.2.0 - middleware](https://github.com/http-interop/http-middleware/tree/ff545c87e97bf4d88f0cb7eb3e89f99aaa53d7a9).
  `Zend\Stratigility\MiddlewarePipe` implements
  `Interop\Http\Middleware\ServerMiddlewareInterface`.
- An object implementing `Zend\Stratigility\MiddlewareInterface`.
  `Zend\Stratigility\MiddlewarePipe` implements this interface.
  (Legacy; this interface is deprecated starting in 1.3.0.)
