<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;

/**
 * Provides base functionality for request and response de/serialization
 * strategies, including functionality for retrieving a line at a time from
 * the message, splitting headers from the body, and serializing headers.
 */
abstract class AbstractSerializer
{
    const CR  = "\r";
    const EOL = "\r\n";
    const LF  = "\n";

    /**
     * Retrieve a single line from the stream.
     *
     * Retrieves a line from the stream; a line is defined as a sequence of
     * characters ending in a CRLF sequence.
     *
     * @param StreamInterface $stream
     * @return string
     * @throws UnexpectedValueException if the sequence contains a CR or LF in
     *     isolation, or ends in a CR.
     */
    protected static function getLine(StreamInterface $stream)
    {
        $line    = '';
        $crFound = false;
        while (! $stream->eof()) {
            $char = $stream->read(1);

            if ($crFound && $char === self::LF) {
                $crFound = false;
                break;
            }

            // CR NOT followed by LF
            if ($crFound && $char !== self::LF) {
                throw new UnexpectedValueException('Unexpected carriage return detected');
            }

            // LF in isolation
            if (! $crFound && $char === self::LF) {
                throw new UnexpectedValueException('Unexpected line feed detected');
            }

            // CR found; do not append
            if ($char === self::CR) {
                $crFound = true;
                continue;
            }

            // Any other character: append
            $line .= $char;
        }

        // CR found at end of stream
        if ($crFound) {
            throw new UnexpectedValueException("Unexpected end of headers");
        }

        return $line;
    }

    /**
     * Split the stream into headers and body content.
     *
     * Returns an array containing two elements
     *
     * - The first is an array of headers
     * - The second is a StreamInterface containing the body content
     *
     * @param StreamInterface $stream
     * @return array
     * @throws UnexpectedValueException For invalid headers.
     */
    protected static function splitStream(StreamInterface $stream)
    {
        $headers       = [];
        $currentHeader = false;

        while ($line = self::getLine($stream)) {
            if (preg_match(';^(?P<name>[!#$%&\'*+.^_`\|~0-9a-zA-Z-]+):(?P<value>.*)$;', $line, $matches)) {
                $currentHeader = $matches['name'];
                if (! isset($headers[$currentHeader])) {
                    $headers[$currentHeader] = [];
                }
                $headers[$currentHeader][] = ltrim($matches['value']);
                continue;
            }

            if (! $currentHeader) {
                throw new UnexpectedValueException('Invalid header detected');
            }

            if (! preg_match('#^[ \t]#', $line)) {
                throw new UnexpectedValueException('Invalid header continuation');
            }

            // Append continuation to last header value found
            $value = array_pop($headers[$currentHeader]);
            $headers[$currentHeader][] = $value . ltrim($line);
        }

        // use RelativeStream to avoid copying initial stream into memory
        return [$headers, new RelativeStream($stream, $stream->tell())];
    }

    /**
     * Serialize headers to string values.
     *
     * @param array $headers
     * @return string
     */
    protected static function serializeHeaders(array $headers)
    {
        $lines = [];
        foreach ($headers as $header => $values) {
            $normalized = self::filterHeader($header);
            foreach ($values as $value) {
                $lines[] = sprintf('%s: %s', $normalized, $value);
            }
        }

        return implode("\r\n", $lines);
    }

    /**
     * Filter a header name to wordcase
     *
     * @param string $header
     * @return string
     */
    protected static function filterHeader($header)
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);
        return str_replace(' ', '-', $filtered);
    }
}
