<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-stratigility for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-stratigility/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Stratigility\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Decorator for PSR ServerRequestInterface
 *
 * Decorates the PSR incoming request interface to add the ability to
 * manipulate arbitrary instance members.
 *
 * @deprecated since 1.3.0; to be removed with 2.0.0. Track the original
 *     request via a request attribute or via a service instead; you can
 *     use Zend\Stratigility\Middleware\OriginalMessages to do so.
 */
class Request implements ServerRequestInterface
{
    /**
     * Original ServerRequestInterface instance.
     *
     * @var mixed
     */
    private $originalRequest;

    /**
     * The currently decorated ServerRequestInterface instance; it may or may
     * not be the same as the originalRequest, depending on how many changes
     * have been pushed to the original.
     *
     * @var ServerRequestInterface
     */
    private $psrRequest;

    /**
     * @param ServerRequestInterface $decoratedRequest
     * @param null|ServerRequestInterface $originalRequest
     */
    public function __construct(
        ServerRequestInterface $decoratedRequest,
        ServerRequestInterface $originalRequest = null
    ) {
        if (null === $originalRequest) {
            $originalRequest = $decoratedRequest;
        }

        $this->originalRequest = $originalRequest;
        $this->psrRequest      = $decoratedRequest
            ->withAttribute('originalUri', $originalRequest->getUri())
            ->withAttribute('originalRequest', $originalRequest);
    }

    /**
     * Return the currently decorated PSR request instance
     *
     * @return ServerRequestInterface
     */
    public function getCurrentRequest()
    {
        trigger_error(sprintf(
            '%s is now deprecated. The request passed to your method is the current '
            . 'request now. %s will no longer be available starting in Stratigility 2.0.0. '
            . 'Please see '
            . 'https://docs.zendframework.com/zend-stratigility/migration/to-v2/#original-request-response-and-uri '
            . 'for full details.',
            __CLASS__,
            \Zend\Stratigility\Middleware\OriginalMessages::class,
            __METHOD__
        ), E_USER_DEPRECATED);

        return $this->psrRequest;
    }

    /**
     * Return the original PSR request instance
     *
     * @return ServerRequestInterface
     */
    public function getOriginalRequest()
    {
        trigger_error(sprintf(
            '%s is now deprecated. Please register %s as your outermost middleware, '
            . 'and pull the original request via the request "originalRequest" '
            . 'attribute. %s will no longer be available starting in Stratigility 2.0.0. '
            . 'Please see '
            . 'https://docs.zendframework.com/zend-stratigility/migration/to-v2/#original-request-response-and-uri '
            . 'for full details.',
            __CLASS__,
            \Zend\Stratigility\Middleware\OriginalMessages::class,
            __METHOD__
        ), E_USER_DEPRECATED);

        return $this->originalRequest;
    }

    /**
     * Proxy to ServerRequestInterface::getRequestTarget()
     *
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        return $this->psrRequest->getRequestTarget();
    }

    /**
     * Proxy to ServerRequestInterface::getProtocolVersion()
     *
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->psrRequest->getProtocolVersion();
    }

    /**
     * Proxy to ServerRequestInterface::withRequestTarget()
     *
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        $new = $this->psrRequest->withRequestTarget($requestTarget);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::withProtocolVersion()
     *
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        $new = $this->psrRequest->withProtocolVersion($version);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getBody()
     *
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->psrRequest->getBody();
    }

    /**
     * Proxy to ServerRequestInterface::withBody()
     *
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        $new = $this->psrRequest->withBody($body);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getHeaders()
     *
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->psrRequest->getHeaders();
    }

    /**
     * Proxy to ServerRequestInterface::hasHeader()
     *
     * {@inheritdoc}
     */
    public function hasHeader($header)
    {
        return $this->psrRequest->hasHeader($header);
    }

    /**
     * Proxy to ServerRequestInterface::getHeader()
     *
     * {@inheritdoc}
     */
    public function getHeader($header)
    {
        return $this->psrRequest->getHeader($header);
    }

    /**
     * Proxy to ServerRequestInterface::getHeaderLine()
     *
     * {@inheritdoc}
     */
    public function getHeaderLine($header)
    {
        return $this->psrRequest->getHeaderLine($header);
    }

    /**
     * Proxy to ServerRequestInterface::withHeader()
     *
     * {@inheritdoc}
     */
    public function withHeader($header, $value)
    {
        $new = $this->psrRequest->withHeader($header, $value);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::addHeader()
     *
     * {@inheritdoc}
     */
    public function withAddedHeader($header, $value)
    {
        $new = $this->psrRequest->withAddedHeader($header, $value);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::removeHeader()
     *
     * {@inheritdoc}
     */
    public function withoutHeader($header)
    {
        $new = $this->psrRequest->withoutHeader($header);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getMethod()
     *
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->psrRequest->getMethod();
    }

    /**
     * Proxy to ServerRequestInterface::withMethod()
     *
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        $new = $this->psrRequest->withMethod($method);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getUri()
     *
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->psrRequest->getUri();
    }

    /**
     * Allow mutating the URI
     *
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = $this->psrRequest->withUri($uri, $preserveHost);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getServerParams()
     *
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->psrRequest->getServerParams();
    }

    /**
     * Proxy to ServerRequestInterface::getCookieParams()
     *
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->psrRequest->getCookieParams();
    }

    /**
     * Proxy to ServerRequestInterface::withCookieParams()
     *
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = $this->psrRequest->withCookieParams($cookies);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getQueryParams()
     *
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->psrRequest->getQueryParams();
    }

    /**
     * Proxy to ServerRequestInterface::withQueryParams()
     *
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = $this->psrRequest->withQueryParams($query);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getFileParams()
     *
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->psrRequest->getUploadedFiles();
    }

    /**
     * Proxy to ServerRequestInterface::getFileParams()
     *
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->psrRequest->withUploadedFiles($uploadedFiles);
    }

    /**
     * Proxy to ServerRequestInterface::getParsedBody()
     *
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->psrRequest->getParsedBody();
    }

    /**
     * Proxy to ServerRequestInterface::withParsedBody()
     *
     * {@inheritdoc}
     */
    public function withParsedBody($params)
    {
        $new = $this->psrRequest->withParsedBody($params);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::getAttributes()
     *
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->psrRequest->getAttributes();
    }

    /**
     * Proxy to ServerRequestInterface::getAttribute()
     *
     * {@inheritdoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        return $this->psrRequest->getAttribute($attribute, $default);
    }

    /**
     * Proxy to ServerRequestInterface::withAttribute()
     *
     * {@inheritdoc}
     */
    public function withAttribute($attribute, $value)
    {
        $new = $this->psrRequest->withAttribute($attribute, $value);
        return new self($new, $this->originalRequest);
    }

    /**
     * Proxy to ServerRequestInterface::withoutAttribute()
     *
     * {@inheritdoc}
     */
    public function withoutAttribute($attribute)
    {
        $new = $this->psrRequest->withoutAttribute($attribute);
        return new self($new, $this->originalRequest);
    }
}
