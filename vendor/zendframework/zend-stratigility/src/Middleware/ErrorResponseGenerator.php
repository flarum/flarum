<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility\Middleware;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Zend\Escaper\Escaper;
use Zend\Stratigility\Utils;

final class ErrorResponseGenerator
{
    /**
     * @var bool
     */
    private $isDevelopmentMode;

    /**
     * @param bool $isDevelopmentMode
     */
    public function __construct($isDevelopmentMode = false)
    {
        $this->isDevelopmentMode = (bool) $isDevelopmentMode;
    }

    /**
     * Create/update the response representing the error.
     *
     * @param Throwable|Exception $e
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke($e, ServerRequestInterface $request, ResponseInterface $response)
    {
        $response = $response->withStatus(Utils::getStatusCode($e, $response));
        $body = $response->getBody();

        if ($this->isDevelopmentMode) {
            $escaper = new Escaper();
            $body->write($escaper->escapeHtml((string) $e));
            return $response;
        }

        $body->write($response->getReasonPhrase() ?: 'Unknown Error');
        return $response;
    }
}
