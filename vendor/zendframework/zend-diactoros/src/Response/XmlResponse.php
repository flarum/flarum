<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * XML response.
 *
 * Allows creating a response by passing an XML string to the constructor; by default,
 * sets a status code of 200 and sets the Content-Type header to application/xml.
 */
class XmlResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create an XML response.
     *
     * Produces an XML response with a Content-Type of application/xml and a default
     * status of 200.
     *
     * @param string|StreamInterface $xml String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws InvalidArgumentException if $text is neither a string or stream.
     */
    public function __construct(
        $xml,
        $status = 200,
        array $headers = []
    ) {
        parent::__construct(
            $this->createBody($xml),
            $status,
            $this->injectContentType('application/xml; charset=utf-8', $headers)
        );
    }

    /**
     * Create the message body.
     *
     * @param string|StreamInterface $xml
     * @return StreamInterface
     * @throws InvalidArgumentException if $xml is neither a string or stream.
     */
    private function createBody($xml)
    {
        if ($xml instanceof StreamInterface) {
            return $xml;
        }

        if (! is_string($xml)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                (is_object($xml) ? get_class($xml) : gettype($xml)),
                __CLASS__
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($xml);
        $body->rewind();
        return $body;
    }
}
