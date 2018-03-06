<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * A class representing empty HTTP responses.
 */
class EmptyResponse extends Response
{
    /**
     * Create an empty response with the given status code.
     *
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     */
    public function __construct($status = 204, array $headers = [])
    {
        $body = new Stream('php://temp', 'r');
        parent::__construct($body, $status, $headers);
    }

    /**
     * Create an empty response with the given headers.
     *
     * @param array $headers Headers for the response.
     * @return EmptyResponse
     */
    public static function withHeaders(array $headers)
    {
        return new static(204, $headers);
    }
}
