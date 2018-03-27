<?php

namespace Franzl\Middleware\Whoops;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Detect any of the supported preferred formats from a HTTP request
 */
class FormatNegotiator
{
    /**
     * @var array Available formats with MIME types
     */
    private static $formats = [
        'html' => ['text/html', 'application/xhtml+xml'],
        'json' => ['application/json', 'text/json', 'application/x-json'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'txt' => ['text/plain']
    ];

    /**
     * Returns the preferred format based on the Accept header
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getPreferredFormat(ServerRequestInterface $request)
    {
        $acceptTypes = $request->getHeader('accept');

        if (count($acceptTypes) > 0) {
            $acceptType = $acceptTypes[0];

            // As many formats may match for a given Accept header, let's try to find the one that fits the best
            $counters = [];
            foreach (self::$formats as $format => $values) {
                foreach ($values as $value) {
                    $counters[$format] = isset($counters[$format]) ? $counters[$format] : 0;
                    $counters[$format] += intval(strpos($acceptType, $value) !== false);
                }
            }

            // Sort the array to retrieve the format that best matches the Accept header
            asort($counters);
            end($counters);
            return key($counters);
        }

        return 'html';
    }
}
