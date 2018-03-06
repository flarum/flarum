<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;
use Zend\Diactoros\AbstractSerializer;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

final class Serializer extends AbstractSerializer
{
    /**
     * Deserialize a response string to a response instance.
     *
     * @param string $message
     * @return Response
     * @throws UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromString($message)
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($message);
        return static::fromStream($stream);
    }

    /**
     * Parse a response from a stream.
     *
     * @param StreamInterface $stream
     * @return Response
     * @throws InvalidArgumentException when the stream is not readable.
     * @throws UnexpectedValueException when errors occur parsing the message.
     */
    public static function fromStream(StreamInterface $stream)
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        list($version, $status, $reasonPhrase) = self::getStatusLine($stream);
        list($headers, $body)                  = self::splitStream($stream);

        return (new Response($body, $status, $headers))
            ->withProtocolVersion($version)
            ->withStatus((int) $status, $reasonPhrase);
    }

    /**
     * Create a string representation of a response.
     *
     * @param ResponseInterface $response
     * @return string
     */
    public static function toString(ResponseInterface $response)
    {
        $reasonPhrase = $response->getReasonPhrase();
        $headers      = self::serializeHeaders($response->getHeaders());
        $body         = (string) $response->getBody();
        $format       = 'HTTP/%s %d%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }

        $headers .= "\r\n\r\n";

        return sprintf(
            $format,
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : ''),
            $headers,
            $body
        );
    }

    /**
     * Retrieve the status line for the message.
     *
     * @param StreamInterface $stream
     * @return array Array with three elements: 0 => version, 1 => status, 2 => reason
     * @throws UnexpectedValueException if line is malformed
     */
    private static function getStatusLine(StreamInterface $stream)
    {
        $line = self::getLine($stream);

        if (! preg_match(
            '#^HTTP/(?P<version>[1-9]\d*\.\d) (?P<status>[1-5]\d{2})(\s+(?P<reason>.+))?$#',
            $line,
            $matches
        )) {
            throw new UnexpectedValueException('No status line detected');
        }

        return [$matches['version'], $matches['status'], isset($matches['reason']) ? $matches['reason'] : ''];
    }
}
