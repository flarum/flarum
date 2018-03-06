<?php
/**
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Request;

use Psr\Http\Message\RequestInterface;
use UnexpectedValueException;
use Zend\Diactoros\Request;
use Zend\Diactoros\Stream;

/**
 * Serialize or deserialize request messages to/from arrays.
 *
 * This class provides functionality for serializing a RequestInterface instance
 * to an array, as well as the reverse operation of creating a Request instance
 * from an array representing a message.
 */
final class ArraySerializer
{
    /**
     * Serialize a request message to an array.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function toArray(RequestInterface $request)
    {
        return [
            'method'           => $request->getMethod(),
            'request_target'   => $request->getRequestTarget(),
            'uri'              => (string) $request->getUri(),
            'protocol_version' => $request->getProtocolVersion(),
            'headers'          => $request->getHeaders(),
            'body'             => (string) $request->getBody(),
        ];
    }

    /**
     * Deserialize a request array to a request instance.
     *
     * @param array $serializedRequest
     * @return Request
     * @throws UnexpectedValueException when cannot deserialize response
     */
    public static function fromArray(array $serializedRequest)
    {
        try {
            $uri             = self::getValueFromKey($serializedRequest, 'uri');
            $method          = self::getValueFromKey($serializedRequest, 'method');
            $body            = new Stream('php://memory', 'wb+');
            $body->write(self::getValueFromKey($serializedRequest, 'body'));
            $headers         = self::getValueFromKey($serializedRequest, 'headers');
            $requestTarget   = self::getValueFromKey($serializedRequest, 'request_target');
            $protocolVersion = self::getValueFromKey($serializedRequest, 'protocol_version');

            return (new Request($uri, $method, $body, $headers))
                ->withRequestTarget($requestTarget)
                ->withProtocolVersion($protocolVersion);
        } catch (\Exception $exception) {
            throw new UnexpectedValueException('Cannot deserialize request', null, $exception);
        }
    }

    /**
     * @param array $data
     * @param string $key
     * @param string $message
     * @return mixed
     * @throws UnexpectedValueException
     */
    private static function getValueFromKey(array $data, $key, $message = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }
        if ($message === null) {
            $message = sprintf('Missing "%s" key in serialized request', $key);
        }
        throw new UnexpectedValueException($message);
    }
}
