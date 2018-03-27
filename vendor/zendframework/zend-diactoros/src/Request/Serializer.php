<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Request;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;
use Zend\Diactoros\AbstractSerializer;
use Zend\Diactoros\Request;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

/**
 * Serialize (cast to string) or deserialize (cast string to Request) messages.
 *
 * This class provides functionality for serializing a RequestInterface instance
 * to a string, as well as the reverse operation of creating a Request instance
 * from a string/stream representing a message.
 */
final class Serializer extends AbstractSerializer
{
    /**
     * Deserialize a request string to a request instance.
     *
     * Internally, casts the message to a stream and invokes fromStream().
     *
     * @param string $message
     * @return Request
     * @throws UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromString($message)
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($message);
        return self::fromStream($stream);
    }

    /**
     * Deserialize a request stream to a request instance.
     *
     * @param StreamInterface $stream
     * @return Request
     * @throws UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromStream(StreamInterface $stream)
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        list($method, $requestTarget, $version) = self::getRequestLine($stream);
        $uri = self::createUriFromRequestTarget($requestTarget);

        list($headers, $body) = self::splitStream($stream);

        return (new Request($uri, $method, $body, $headers))
            ->withProtocolVersion($version)
            ->withRequestTarget($requestTarget);
    }

    /**
     * Serialize a request message to a string.
     *
     * @param RequestInterface $request
     * @return string
     */
    public static function toString(RequestInterface $request)
    {
        $httpMethod = $request->getMethod();
        if (empty($httpMethod)) {
            throw new UnexpectedValueException('Object can not be serialized because HTTP method is empty');
        }
        $headers = self::serializeHeaders($request->getHeaders());
        $body    = (string) $request->getBody();
        $format  = '%s %s HTTP/%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }
        if (! empty($body)) {
            $headers .= "\r\n\r\n";
        }

        return sprintf(
            $format,
            $httpMethod,
            $request->getRequestTarget(),
            $request->getProtocolVersion(),
            $headers,
            $body
        );
    }

    /**
     * Retrieve the components of the request line.
     *
     * Retrieves the first line of the stream and parses it, raising an
     * exception if it does not follow specifications; if valid, returns a list
     * with the method, target, and version, in that order.
     *
     * @param StreamInterface $stream
     * @return array
     */
    private static function getRequestLine(StreamInterface $stream)
    {
        $requestLine = self::getLine($stream);

        if (! preg_match(
            '#^(?P<method>[!\#$%&\'*+.^_`|~a-zA-Z0-9-]+) (?P<target>[^\s]+) HTTP/(?P<version>[1-9]\d*\.\d+)$#',
            $requestLine,
            $matches
        )) {
            throw new UnexpectedValueException('Invalid request line detected');
        }

        return [$matches['method'], $matches['target'], $matches['version']];
    }

    /**
     * Create and return a Uri instance based on the provided request target.
     *
     * If the request target is of authority or asterisk form, an empty Uri
     * instance is returned; otherwise, the value is used to create and return
     * a new Uri instance.
     *
     * @param string $requestTarget
     * @return Uri
     */
    private static function createUriFromRequestTarget($requestTarget)
    {
        if (preg_match('#^https?://#', $requestTarget)) {
            return new Uri($requestTarget);
        }

        if (preg_match('#^(\*|[^/])#', $requestTarget)) {
            return new Uri();
        }

        return new Uri($requestTarget);
    }
}
