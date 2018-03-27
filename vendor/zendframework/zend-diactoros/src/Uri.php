<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Implementation of Psr\Http\UriInterface.
 *
 * Provides a value object representing a URI for HTTP requests.
 *
 * Instances of this class  are considered immutable; all methods that
 * might change state are implemented such that they retain the internal
 * state of the current instance and return a new instance that contains the
 * changed state.
 */
class Uri implements UriInterface
{
    /**
     * Sub-delimiters used in user info, query strings and fragments.
     *
     * @const string
     */
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * Unreserved characters used in user info, paths, query strings, and fragments.
     *
     * @const string
     */
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~\pL';

    /**
     * @var int[] Array indexed by valid scheme names to their corresponding ports.
     */
    protected $allowedSchemes = [
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * @var string
     */
    private $scheme = '';

    /**
     * @var string
     */
    private $userInfo = '';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $query = '';

    /**
     * @var string
     */
    private $fragment = '';

    /**
     * generated uri string cache
     * @var string|null
     */
    private $uriString;

    /**
     * @param string $uri
     * @throws InvalidArgumentException on non-string $uri argument
     */
    public function __construct($uri = '')
    {
        if (! is_string($uri)) {
            throw new InvalidArgumentException(sprintf(
                'URI passed to constructor must be a string; received "%s"',
                (is_object($uri) ? get_class($uri) : gettype($uri))
            ));
        }

        if (! empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * Operations to perform on clone.
     *
     * Since cloning usually is for purposes of mutation, we reset the
     * $uriString property so it will be re-calculated.
     */
    public function __clone()
    {
        $this->uriString = null;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (null !== $this->uriString) {
            return $this->uriString;
        }

        $this->uriString = static::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->getPath(), // Absolute URIs should use a "/" for an empty path
            $this->query,
            $this->fragment
        );

        return $this->uriString;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;
        if (! empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Retrieve the user-info part of the URI.
     *
     * This value is percent-encoded, per RFC 3986 Section 3.2.1.
     *
     * {@inheritdoc}
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->isNonStandardPort($this->scheme, $this->host, $this->port)
            ? $this->port
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {
        if (! is_string($scheme)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string argument; received %s',
                __METHOD__,
                (is_object($scheme) ? get_class($scheme) : gettype($scheme))
            ));
        }

        $scheme = $this->filterScheme($scheme);

        if ($scheme === $this->scheme) {
            // Do nothing if no change was made.
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    /**
     * Create and return a new instance containing the provided user credentials.
     *
     * The value will be percent-encoded in the new instance, but with measures
     * taken to prevent double-encoding.
     *
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {
        if (! is_string($user)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string user argument; received %s',
                __METHOD__,
                (is_object($user) ? get_class($user) : gettype($user))
            ));
        }
        if (null !== $password && ! is_string($password)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string password argument; received %s',
                __METHOD__,
                (is_object($password) ? get_class($password) : gettype($password))
            ));
        }

        $info = $this->filterUserInfoPart($user);
        if ($password) {
            $info .= ':' . $this->filterUserInfoPart($password);
        }

        if ($info === $this->userInfo) {
            // Do nothing if no change was made.
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {
        if (! is_string($host)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string argument; received %s',
                __METHOD__,
                (is_object($host) ? get_class($host) : gettype($host))
            ));
        }

        if ($host === $this->host) {
            // Do nothing if no change was made.
            return $this;
        }

        $new = clone $this;
        $new->host = strtolower($host);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        if (! is_numeric($port) && $port !== null) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%s" specified; must be an integer, an integer string, or null',
                (is_object($port) ? get_class($port) : gettype($port))
            ));
        }

        if ($port !== null) {
            $port = (int) $port;
        }

        if ($port === $this->port) {
            // Do nothing if no change was made.
            return $this;
        }

        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%d" specified; must be a valid TCP/UDP port',
                $port
            ));
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        if (! is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided; must be a string'
            );
        }

        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a query string'
            );
        }

        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a URI fragment'
            );
        }

        $path = $this->filterPath($path);

        if ($path === $this->path) {
            // Do nothing if no change was made.
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        if (! is_string($query)) {
            throw new InvalidArgumentException(
                'Query string must be a string'
            );
        }

        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException(
                'Query string must not include a URI fragment'
            );
        }

        $query = $this->filterQuery($query);

        if ($query === $this->query) {
            // Do nothing if no change was made.
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        if (! is_string($fragment)) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a string argument; received %s',
                __METHOD__,
                (is_object($fragment) ? get_class($fragment) : gettype($fragment))
            ));
        }

        $fragment = $this->filterFragment($fragment);

        if ($fragment === $this->fragment) {
            // Do nothing if no change was made.
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * Parse a URI into its parts, and set the properties
     *
     * @param string $uri
     */
    private function parseUri($uri)
    {
        $parts = parse_url($uri);

        if (false === $parts) {
            throw new \InvalidArgumentException(
                'The source URI string appears to be malformed'
            );
        }

        $this->scheme    = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo  = isset($parts['user']) ? $this->filterUserInfoPart($parts['user']) : '';
        $this->host      = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port      = isset($parts['port']) ? $parts['port'] : null;
        $this->path      = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query     = isset($parts['query']) ? $this->filterQuery($parts['query']) : '';
        $this->fragment  = isset($parts['fragment']) ? $this->filterFragment($parts['fragment']) : '';

        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * Create a URI string from its various parts
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private static function createUriString($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';

        if (! empty($scheme)) {
            $uri .= sprintf('%s:', $scheme);
        }

        if (! empty($authority)) {
            $uri .= '//' . $authority;
        }

        if ($path) {
            if (empty($path) || '/' !== substr($path, 0, 1)) {
                $path = '/' . $path;
            }

            $uri .= $path;
        }

        if ($query) {
            $uri .= sprintf('?%s', $query);
        }

        if ($fragment) {
            $uri .= sprintf('#%s', $fragment);
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @return bool
     */
    private function isNonStandardPort($scheme, $host, $port)
    {
        if (! $scheme) {
            if ($host && ! $port) {
                return false;
            }
            return true;
        }

        if (! $host || ! $port) {
            return false;
        }

        return ! isset($this->allowedSchemes[$scheme]) || $port !== $this->allowedSchemes[$scheme];
    }

    /**
     * Filters the scheme to ensure it is a valid scheme.
     *
     * @param string $scheme Scheme name.
     *
     * @return string Filtered scheme.
     */
    private function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (empty($scheme)) {
            return '';
        }

        if (! array_key_exists($scheme, $this->allowedSchemes)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s"; must be any empty string or in the set (%s)',
                $scheme,
                implode(', ', array_keys($this->allowedSchemes))
            ));
        }

        return $scheme;
    }

    /**
     * Filters a part of user info in a URI to ensure it is properly encoded.
     *
     * @param string $part
     * @return string
     */
    private function filterUserInfoPart($part)
    {
        // Note the addition of `%` to initial charset; this allows `|` portion
        // to match and thus prevent double-encoding.
        return preg_replace_callback(
            '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $part
        );
    }

    /**
     * Filters the path of a URI to ensure it is properly encoded.
     *
     * @param string $path
     * @return string
     */
    private function filterPath($path)
    {
        $path = preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $path
        );

        if (empty($path)) {
            // No path
            return $path;
        }

        if ($path[0] !== '/') {
            // Relative path
            return $path;
        }

        // Ensure only one leading slash, to prevent XSS attempts.
        return '/' . ltrim($path, '/');
    }

    /**
     * Filter a query string to ensure it is propertly encoded.
     *
     * Ensures that the values in the query string are properly urlencoded.
     *
     * @param string $query
     * @return string
     */
    private function filterQuery($query)
    {
        if (! empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            list($key, $value) = $this->splitQueryValue($part);
            if ($value === null) {
                $parts[$index] = $this->filterQueryOrFragment($key);
                continue;
            }
            $parts[$index] = sprintf(
                '%s=%s',
                $this->filterQueryOrFragment($key),
                $this->filterQueryOrFragment($value)
            );
        }

        return implode('&', $parts);
    }

    /**
     * Split a query value into a key/value tuple.
     *
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    private function splitQueryValue($value)
    {
        $data = explode('=', $value, 2);
        if (1 === count($data)) {
            $data[] = null;
        }
        return $data;
    }

    /**
     * Filter a fragment value to ensure it is properly encoded.
     *
     * @param string $fragment
     * @return string
     */
    private function filterFragment($fragment)
    {
        if (! empty($fragment) && strpos($fragment, '#') === 0) {
            $fragment = '%23' . substr($fragment, 1);
        }

        return $this->filterQueryOrFragment($fragment);
    }

    /**
     * Filter a query string key or value, or a fragment.
     *
     * @param string $value
     * @return string
     */
    private function filterQueryOrFragment($value)
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $value
        );
    }

    /**
     * URL encode a character returned by a regex.
     *
     * @param array $matches
     * @return string
     */
    private function urlEncodeChar(array $matches)
    {
        return rawurlencode($matches[0]);
    }
}
