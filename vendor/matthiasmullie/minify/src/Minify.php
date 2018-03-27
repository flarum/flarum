<?php
/**
 * Abstract minifier class
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved
 * @license MIT License
 */
namespace MatthiasMullie\Minify;

use MatthiasMullie\Minify\Exceptions\IOException;
use Psr\Cache\CacheItemInterface;

/**
 * Abstract minifier class.
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @package Minify
 * @author Matthias Mullie <minify@mullie.eu>
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved
 * @license MIT License
 */
abstract class Minify
{
    /**
     * The data to be minified.
     *
     * @var string[]
     */
    protected $data = array();

    /**
     * Array of patterns to match.
     *
     * @var string[]
     */
    protected $patterns = array();

    /**
     * This array will hold content of strings and regular expressions that have
     * been extracted from the JS source code, so we can reliably match "code",
     * without having to worry about potential "code-like" characters inside.
     *
     * @var string[]
     */
    public $extracted = array();

    /**
     * Init the minify class - optionally, code may be passed along already.
     */
    public function __construct(/* $data = null, ... */)
    {
        // it's possible to add the source through the constructor as well ;)
        if (func_num_args()) {
            call_user_func_array(array($this, 'add'), func_get_args());
        }
    }

    /**
     * Add a file or straight-up code to be minified.
     *
     * @param string|string[] $data
     *
     * @return static
     */
    public function add($data /* $data = null, ... */)
    {
        // bogus "usage" of parameter $data: scrutinizer warns this variable is
        // not used (we're using func_get_args instead to support overloading),
        // but it still needs to be defined because it makes no sense to have
        // this function without argument :)
        $args = array($data) + func_get_args();

        // this method can be overloaded
        foreach ($args as $data) {
            if (is_array($data)) {
                call_user_func_array(array($this, 'add'), $data);
                continue;
            }

            // redefine var
            $data = (string) $data;

            // load data
            $value = $this->load($data);
            $key = ($data != $value) ? $data : count($this->data);

            // replace CR linefeeds etc.
            // @see https://github.com/matthiasmullie/minify/pull/139
            $value = str_replace(array("\r\n", "\r"), "\n", $value);

            // store data
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Minify the data & (optionally) saves it to a file.
     *
     * @param string[optional] $path Path to write the data to
     *
     * @return string The minified data
     */
    public function minify($path = null)
    {
        $content = $this->execute($path);

        // save to path
        if ($path !== null) {
            $this->save($content, $path);
        }

        return $content;
    }

    /**
     * Minify & gzip the data & (optionally) saves it to a file.
     *
     * @param string[optional] $path  Path to write the data to
     * @param int[optional]    $level Compression level, from 0 to 9
     *
     * @return string The minified & gzipped data
     */
    public function gzip($path = null, $level = 9)
    {
        $content = $this->execute($path);
        $content = gzencode($content, $level, FORCE_GZIP);

        // save to path
        if ($path !== null) {
            $this->save($content, $path);
        }

        return $content;
    }

    /**
     * Minify the data & write it to a CacheItemInterface object.
     *
     * @param CacheItemInterface $item Cache item to write the data to
     *
     * @return CacheItemInterface Cache item with the minifier data
     */
    public function cache(CacheItemInterface $item)
    {
        $content = $this->execute();
        $item->set($content);

        return $item;
    }

    /**
     * Minify the data.
     *
     * @param string[optional] $path Path to write the data to
     *
     * @return string The minified data
     */
    abstract public function execute($path = null);

    /**
     * Load data.
     *
     * @param string $data Either a path to a file or the content itself
     *
     * @return string
     */
    protected function load($data)
    {
        // check if the data is a file
        if ($this->canImportFile($data)) {
            $data = file_get_contents($data);

            // strip BOM, if any
            if (substr($data, 0, 3) == "\xef\xbb\xbf") {
                $data = substr($data, 3);
            }
        }

        return $data;
    }

    /**
     * Save to file.
     *
     * @param string $content The minified data
     * @param string $path    The path to save the minified data to
     *
     * @throws IOException
     */
    protected function save($content, $path)
    {
        $handler = $this->openFileForWriting($path);

        $this->writeToFile($handler, $content);

        @fclose($handler);
    }

    /**
     * Register a pattern to execute against the source content.
     *
     * @param string          $pattern     PCRE pattern
     * @param string|callable $replacement Replacement value for matched pattern
     */
    protected function registerPattern($pattern, $replacement = '')
    {
        // study the pattern, we'll execute it more than once
        $pattern .= 'S';

        $this->patterns[] = array($pattern, $replacement);
    }

    /**
     * We can't "just" run some regular expressions against JavaScript: it's a
     * complex language. E.g. having an occurrence of // xyz would be a comment,
     * unless it's used within a string. Of you could have something that looks
     * like a 'string', but inside a comment.
     * The only way to accurately replace these pieces is to traverse the JS one
     * character at a time and try to find whatever starts first.
     *
     * @param string $content The content to replace patterns in
     *
     * @return string The (manipulated) content
     */
    protected function replace($content)
    {
        $processed = '';
        $positions = array_fill(0, count($this->patterns), -1);
        $matches = array();

        while ($content) {
            // find first match for all patterns
            foreach ($this->patterns as $i => $pattern) {
                list($pattern, $replacement) = $pattern;

                // no need to re-run matches that are still in the part of the
                // content that hasn't been processed
                if ($positions[$i] >= 0) {
                    continue;
                }

                $match = null;
                if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
                    $matches[$i] = $match;

                    // we'll store the match position as well; that way, we
                    // don't have to redo all preg_matches after changing only
                    // the first (we'll still know where those others are)
                    $positions[$i] = $match[0][1];
                } else {
                    // if the pattern couldn't be matched, there's no point in
                    // executing it again in later runs on this same content;
                    // ignore this one until we reach end of content
                    unset($matches[$i]);
                    $positions[$i] = strlen($content);
                }
            }

            // no more matches to find: everything's been processed, break out
            if (!$matches) {
                $processed .= $content;
                break;
            }

            // see which of the patterns actually found the first thing (we'll
            // only want to execute that one, since we're unsure if what the
            // other found was not inside what the first found)
            $discardLength = min($positions);
            $firstPattern = array_search($discardLength, $positions);
            $match = $matches[$firstPattern][0][0];

            // execute the pattern that matches earliest in the content string
            list($pattern, $replacement) = $this->patterns[$firstPattern];
            $replacement = $this->replacePattern($pattern, $replacement, $content);

            // figure out which part of the string was unmatched; that's the
            // part we'll execute the patterns on again next
            $content = (string) substr($content, $discardLength);
            $unmatched = (string) substr($content, strpos($content, $match) + strlen($match));

            // move the replaced part to $processed and prepare $content to
            // again match batch of patterns against
            $processed .= substr($replacement, 0, strlen($replacement) - strlen($unmatched));
            $content = $unmatched;

            // first match has been replaced & that content is to be left alone,
            // the next matches will start after this replacement, so we should
            // fix their offsets
            foreach ($positions as $i => $position) {
                $positions[$i] -= $discardLength + strlen($match);
            }
        }

        return $processed;
    }

    /**
     * This is where a pattern is matched against $content and the matches
     * are replaced by their respective value.
     * This function will be called plenty of times, where $content will always
     * move up 1 character.
     *
     * @param string          $pattern     Pattern to match
     * @param string|callable $replacement Replacement value
     * @param string          $content     Content to match pattern against
     *
     * @return string
     */
    protected function replacePattern($pattern, $replacement, $content)
    {
        if (is_callable($replacement)) {
            return preg_replace_callback($pattern, $replacement, $content, 1, $count);
        } else {
            return preg_replace($pattern, $replacement, $content, 1, $count);
        }
    }

    /**
     * Strings are a pattern we need to match, in order to ignore potential
     * code-like content inside them, but we just want all of the string
     * content to remain untouched.
     *
     * This method will replace all string content with simple STRING#
     * placeholder text, so we've rid all strings from characters that may be
     * misinterpreted. Original string content will be saved in $this->extracted
     * and after doing all other minifying, we can restore the original content
     * via restoreStrings().
     *
     * @param string[optional] $chars
     * @param string[optional] $placeholderPrefix
     */
    protected function extractStrings($chars = '\'"', $placeholderPrefix = '')
    {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function ($match) use ($minifier, $placeholderPrefix) {
            // check the second index here, because the first always contains a quote
            if ($match[2] === '') {
                /*
                 * Empty strings need no placeholder; they can't be confused for
                 * anything else anyway.
                 * But we still needed to match them, for the extraction routine
                 * to skip over this particular string.
                 */
                return $match[0];
            }

            $count = count($minifier->extracted);
            $placeholder = $match[1].$placeholderPrefix.$count.$match[1];
            $minifier->extracted[$placeholder] = $match[1].$match[2].$match[1];

            return $placeholder;
        };

        /*
         * The \\ messiness explained:
         * * Don't count ' or " as end-of-string if it's escaped (has backslash
         * in front of it)
         * * Unless... that backslash itself is escaped (another leading slash),
         * in which case it's no longer escaping the ' or "
         * * So there can be either no backslash, or an even number
         * * multiply all of that times 4, to account for the escaping that has
         * to be done to pass the backslash into the PHP string without it being
         * considered as escape-char (times 2) and to get it in the regex,
         * escaped (times 2)
         */
        $this->registerPattern('/(['.$chars.'])(.*?(?<!\\\\)(\\\\\\\\)*+)\\1/s', $callback);
    }

    /**
     * This method will restore all extracted data (strings, regexes) that were
     * replaced with placeholder text in extract*(). The original content was
     * saved in $this->extracted.
     *
     * @param string $content
     *
     * @return string
     */
    protected function restoreExtractedData($content)
    {
        if (!$this->extracted) {
            // nothing was extracted, nothing to restore
            return $content;
        }

        $content = strtr($content, $this->extracted);

        $this->extracted = array();

        return $content;
    }

    /**
     * Check if the path is a regular file and can be read.
     *
     * @param string $path
     *
     * @return bool
     */
    protected function canImportFile($path)
    {
        $parsed = parse_url($path);
        if (
            // file is elsewhere
            isset($parsed['host']) ||
            // file responds to queries (may change, or need to bypass cache)
            isset($parsed['query'])
        ) {
            return false;
        }

        return strlen($path) < PHP_MAXPATHLEN && @is_file($path) && is_readable($path);
    }

    /**
     * Attempts to open file specified by $path for writing.
     *
     * @param string $path The path to the file
     *
     * @return resource Specifier for the target file
     *
     * @throws IOException
     */
    protected function openFileForWriting($path)
    {
        if (($handler = @fopen($path, 'w')) === false) {
            throw new IOException('The file "'.$path.'" could not be opened for writing. Check if PHP has enough permissions.');
        }

        return $handler;
    }

    /**
     * Attempts to write $content to the file specified by $handler. $path is used for printing exceptions.
     *
     * @param resource $handler The resource to write to
     * @param string   $content The content to write
     * @param string   $path    The path to the file (for exception printing only)
     *
     * @throws IOException
     */
    protected function writeToFile($handler, $content, $path = '')
    {
        if (($result = @fwrite($handler, $content)) === false || ($result < strlen($content))) {
            throw new IOException('The file "'.$path.'" could not be written to. Check your disk space and file permissions.');
        }
    }
}
