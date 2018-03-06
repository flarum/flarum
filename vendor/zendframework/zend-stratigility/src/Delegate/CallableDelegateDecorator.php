<?php
/**
 * @link      http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Stratigility\Delegate;

use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorate callable delegates as http-interop delegates in order to process
 * incoming requests.
 */
class CallableDelegateDecorator implements DelegateInterface
{
    /**
     * @var callable
     */
    private $delegate;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param callable $delegate
     * @param ResponseInterface $response
     */
    public function __construct(callable $delegate, ResponseInterface $response)
    {
        $this->delegate = $delegate;
        $this->response = $response;
    }

    /**
     * Proxies to the underlying callable delegate to process a request.
     *
     * {@inheritDoc}
     */
    public function process(RequestInterface $request)
    {
        $delegate = $this->delegate;
        return $delegate($request, $this->response);
    }
}
