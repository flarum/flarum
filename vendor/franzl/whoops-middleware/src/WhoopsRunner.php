<?php

namespace Franzl\Middleware\Whoops;

use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Zend\Diactoros\Response\HtmlResponse;

class WhoopsRunner
{
    public static function handle($error, ServerRequestInterface $request)
    {
        $method = Run::EXCEPTION_HANDLER;

        $whoops = self::getWhoopsInstance($request);

        // Output is managed by the middleware pipeline
        $whoops->allowQuit(false);
        
        ob_start();
        $whoops->$method($error);
        $response = ob_get_clean();

        return new HtmlResponse($response, 500);
    }

    private static function getWhoopsInstance(ServerRequestInterface $request)
    {
        $whoops = new Run();
        if (php_sapi_name() === 'cli') {
            $whoops->pushHandler(new PlainTextHandler);
            return $whoops;
        }

        $format = FormatNegotiator::getPreferredFormat($request);
        switch ($format) {
            case 'json':
                $handler = new JsonResponseHandler;
                $handler->addTraceToOutput(true);
                break;
            case 'html':
                $handler = new PrettyPageHandler;
                break;
            case 'txt':
                $handler = new PlainTextHandler;
                $handler->addTraceToOutput(true);
                break;
            case 'xml':
                $handler = new XmlResponseHandler;
                $handler->addTraceToOutput(true);
                break;
            default:
                if (empty($format)) {
                    $handler = new PrettyPageHandler;
                } else {
                    $handler = new PlainTextHandler;
                    $handler->addTraceToOutput(true);
                }
        }

        $whoops->pushHandler($handler);
        return $whoops;
    }
}
