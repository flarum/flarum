# Usage

Creating an application consists of 3 steps:

- Create middleware or a middleware pipeline
- Create a server, using the middleware
- Instruct the server to listen for a request

```php
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\NoopFinalHandler;
use Zend\Diactoros\Server;

require __DIR__ . '/../vendor/autoload.php';

$app    = new MiddlewarePipe();
$server = Server::createServer($app,
  $_SERVER,
  $_GET,
  $_POST,
  $_COOKIE,
  $_FILES
);
$server->listen(new NoopFinalHandler());
```

The above example is useless by itself until you pipe middleware into the application.
