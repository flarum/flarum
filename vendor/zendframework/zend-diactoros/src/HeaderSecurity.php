<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use InvalidArgumentException;

/**
 * Provide security tools around HTTP headers to prevent common injection vectors.
 *
 * Code is largely lifted from the Zend\Http\Header\HeaderValue implementation in
 * Zend Framework, released with the copyright and license below.
 *
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
final class HeaderSecurity
{
    /**
     * Private constructor; non-instantiable.
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Filter a header value
     *
     * Ensures CRLF header injection vectors are filtered.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * This method filters any values not allowed from the string, and is
     * lossy.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @return string
     */
    public static function filter($value)
    {
        $value  = (string) $value;
        $length = strlen($value);
        $string = '';
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Detect continuation sequences
            if ($ascii === 13) {
                $lf = ord($value[$i + 1]);
                $ws = ord($value[$i + 2]);
                if ($lf === 10 && in_array($ws, [9, 32], true)) {
                    $string .= $value[$i] . $value[$i + 1];
                    $i += 1;
                }

                continue;
            }

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                continue;
            }

            $string .= $value[$i];
        }

        return $string;
    }

    /**
     * Validate a header value.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @return bool
     */
    public static function isValid($value)
    {
        $value  = (string) $value;

        // Look for:
        // \n not preceded by \r, OR
        // \r not followed by \n, OR
        // \r\n not followed by space or horizontal tab; these are all CRLF attacks
        if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value)) {
            return false;
        }

        // Non-visible, non-whitespace characters
        // 9 === horizontal tab
        // 10 === line feed
        // 13 === carriage return
        // 32-126, 128-254 === visible
        // 127 === DEL (disallowed)
        // 255 === null byte (disallowed)
        if (preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Assert a header value is valid.
     *
     * @param string $value
     * @throws InvalidArgumentException for invalid values
     */
    public static function assertValid($value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid header value type; must be a string or numeric; received %s',
                (is_object($value) ? get_class($value) : gettype($value))
            ));
        }
        if (! self::isValid($value)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid header value',
                $value
            ));
        }
    }

    /**
     * Assert whether or not a header name is valid.
     *
     * @see http://tools.ietf.org/html/rfc7230#section-3.2
     * @param mixed $name
     * @throws InvalidArgumentException
     */
    public static function assertValidName($name)
    {
        if (! is_string($name)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid header name type; expected string; received %s',
                (is_object($name) ? get_class($name) : gettype($name))
            ));
        }
        if (! preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid header name',
                $name
            ));
        }
    }
}
