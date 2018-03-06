<?php
/**
 * CSS Minifier
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved
 * @license MIT License
 */

namespace MatthiasMullie\Minify;

use MatthiasMullie\Minify\Exceptions\FileImportException;
use MatthiasMullie\PathConverter\ConverterInterface;
use MatthiasMullie\PathConverter\Converter;

/**
 * CSS minifier
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @package Minify
 * @author Matthias Mullie <minify@mullie.eu>
 * @author Tijs Verkoyen <minify@verkoyen.eu>
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved
 * @license MIT License
 */
class CSS extends Minify
{
    /**
     * @var int maximum inport size in kB
     */
    protected $maxImportSize = 5;

    /**
     * @var string[] valid import extensions
     */
    protected $importExtensions = array(
        'gif' => 'data:image/gif',
        'png' => 'data:image/png',
        'jpe' => 'data:image/jpeg',
        'jpg' => 'data:image/jpeg',
        'jpeg' => 'data:image/jpeg',
        'svg' => 'data:image/svg+xml',
        'woff' => 'data:application/x-font-woff',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'xbm' => 'image/x-xbitmap',
    );

    /**
     * Set the maximum size if files to be imported.
     *
     * Files larger than this size (in kB) will not be imported into the CSS.
     * Importing files into the CSS as data-uri will save you some connections,
     * but we should only import relatively small decorative images so that our
     * CSS file doesn't get too bulky.
     *
     * @param int $size Size in kB
     */
    public function setMaxImportSize($size)
    {
        $this->maxImportSize = $size;
    }

    /**
     * Set the type of extensions to be imported into the CSS (to save network
     * connections).
     * Keys of the array should be the file extensions & respective values
     * should be the data type.
     *
     * @param string[] $extensions Array of file extensions
     */
    public function setImportExtensions(array $extensions)
    {
        $this->importExtensions = $extensions;
    }

    /**
     * Move any import statements to the top.
     *
     * @param string $content Nearly finished CSS content
     *
     * @return string
     */
    protected function moveImportsToTop($content)
    {
        if (preg_match_all('/(;?)(@import (?<url>url\()?(?P<quotes>["\']?).+?(?P=quotes)(?(url)\)))/', $content, $matches)) {
            // remove from content
            foreach ($matches[0] as $import) {
                $content = str_replace($import, '', $content);
            }

            // add to top
            $content = implode(';', $matches[2]).';'.trim($content, ';');
        }

        return $content;
    }

    /**
     * Combine CSS from import statements.
     *
     * @import's will be loaded and their content merged into the original file,
     * to save HTTP requests.
     *
     * @param string   $source  The file to combine imports for
     * @param string   $content The CSS content to combine imports for
     * @param string[] $parents Parent paths, for circular reference checks
     *
     * @return string
     *
     * @throws FileImportException
     */
    protected function combineImports($source, $content, $parents)
    {
        $importRegexes = array(
            // @import url(xxx)
            '/
            # import statement
            @import

            # whitespace
            \s+

                # open url()
                url\(

                    # (optional) open path enclosure
                    (?P<quotes>["\']?)

                        # fetch path
                        (?P<path>.+?)

                    # (optional) close path enclosure
                    (?P=quotes)

                # close url()
                \)

                # (optional) trailing whitespace
                \s*

                # (optional) media statement(s)
                (?P<media>[^;]*)

                # (optional) trailing whitespace
                \s*

            # (optional) closing semi-colon
            ;?

            /ix',

            // @import 'xxx'
            '/

            # import statement
            @import

            # whitespace
            \s+

                # open path enclosure
                (?P<quotes>["\'])

                    # fetch path
                    (?P<path>.+?)

                # close path enclosure
                (?P=quotes)

                # (optional) trailing whitespace
                \s*

                # (optional) media statement(s)
                (?P<media>[^;]*)

                # (optional) trailing whitespace
                \s*

            # (optional) closing semi-colon
            ;?

            /ix',
        );

        // find all relative imports in css
        $matches = array();
        foreach ($importRegexes as $importRegex) {
            if (preg_match_all($importRegex, $content, $regexMatches, PREG_SET_ORDER)) {
                $matches = array_merge($matches, $regexMatches);
            }
        }

        $search = array();
        $replace = array();

        // loop the matches
        foreach ($matches as $match) {
            // get the path for the file that will be imported
            $importPath = dirname($source).'/'.$match['path'];

            // only replace the import with the content if we can grab the
            // content of the file
            if (!$this->canImportByPath($match['path']) || !$this->canImportFile($importPath)) {
                continue;
            }

            // check if current file was not imported previously in the same
            // import chain.
            if (in_array($importPath, $parents)) {
                throw new FileImportException('Failed to import file "'.$importPath.'": circular reference detected.');
            }

            // grab referenced file & minify it (which may include importing
            // yet other @import statements recursively)
            $minifier = new static($importPath);
            $importContent = $minifier->execute($source, $parents);

            // check if this is only valid for certain media
            if (!empty($match['media'])) {
                $importContent = '@media '.$match['media'].'{'.$importContent.'}';
            }

            // add to replacement array
            $search[] = $match[0];
            $replace[] = $importContent;
        }

        // replace the import statements
        return str_replace($search, $replace, $content);
    }

    /**
     * Import files into the CSS, base64-ized.
     *
     * @url(image.jpg) images will be loaded and their content merged into the
     * original file, to save HTTP requests.
     *
     * @param string $source  The file to import files for
     * @param string $content The CSS content to import files for
     *
     * @return string
     */
    protected function importFiles($source, $content)
    {
        $regex = '/url\((["\']?)(.+?)\\1\)/i';
        if ($this->importExtensions && preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            $search = array();
            $replace = array();

            // loop the matches
            foreach ($matches as $match) {
                $extension = substr(strrchr($match[2], '.'), 1);
                if ($extension && !array_key_exists($extension, $this->importExtensions)) {
                    continue;
                }

                // get the path for the file that will be imported
                $path = $match[2];
                $path = dirname($source).'/'.$path;

                // only replace the import with the content if we're able to get
                // the content of the file, and it's relatively small
                if ($this->canImportFile($path) && $this->canImportBySize($path)) {
                    // grab content && base64-ize
                    $importContent = $this->load($path);
                    $importContent = base64_encode($importContent);

                    // build replacement
                    $search[] = $match[0];
                    $replace[] = 'url('.$this->importExtensions[$extension].';base64,'.$importContent.')';
                }
            }

            // replace the import statements
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    /**
     * Minify the data.
     * Perform CSS optimizations.
     *
     * @param string[optional] $path    Path to write the data to
     * @param string[]         $parents Parent paths, for circular reference checks
     *
     * @return string The minified data
     */
    public function execute($path = null, $parents = array())
    {
        $content = '';

        // loop CSS data (raw data and files)
        foreach ($this->data as $source => $css) {
            /*
             * Let's first take out strings & comments, since we can't just
             * remove whitespace anywhere. If whitespace occurs inside a string,
             * we should leave it alone. E.g.:
             * p { content: "a   test" }
             */
            $this->extractStrings();
            $this->stripComments();
            $css = $this->replace($css);

            $css = $this->stripWhitespace($css);
            $css = $this->shortenHex($css);
            $css = $this->shortenZeroes($css);
            $css = $this->shortenFontWeights($css);
            $css = $this->stripEmptyTags($css);

            // restore the string we've extracted earlier
            $css = $this->restoreExtractedData($css);

            $source = is_int($source) ? '' : $source;
            $parents = $source ? array_merge($parents, array($source)) : $parents;
            $css = $this->combineImports($source, $css, $parents);
            $css = $this->importFiles($source, $css);

            /*
             * If we'll save to a new path, we'll have to fix the relative paths
             * to be relative no longer to the source file, but to the new path.
             * If we don't write to a file, fall back to same path so no
             * conversion happens (because we still want it to go through most
             * of the move code, which also addresses url() & @import syntax...)
             */
            $converter = $this->getPathConverter($source, $path ?: $source);
            $css = $this->move($converter, $css);

            // combine css
            $content .= $css;
        }

        $content = $this->moveImportsToTop($content);

        return $content;
    }

    /**
     * Moving a css file should update all relative urls.
     * Relative references (e.g. ../images/image.gif) in a certain css file,
     * will have to be updated when a file is being saved at another location
     * (e.g. ../../images/image.gif, if the new CSS file is 1 folder deeper).
     *
     * @param ConverterInterface $converter Relative path converter
     * @param string             $content   The CSS content to update relative urls for
     *
     * @return string
     */
    protected function move(ConverterInterface $converter, $content)
    {
        /*
         * Relative path references will usually be enclosed by url(). @import
         * is an exception, where url() is not necessary around the path (but is
         * allowed).
         * This *could* be 1 regular expression, where both regular expressions
         * in this array are on different sides of a |. But we're using named
         * patterns in both regexes, the same name on both regexes. This is only
         * possible with a (?J) modifier, but that only works after a fairly
         * recent PCRE version. That's why I'm doing 2 separate regular
         * expressions & combining the matches after executing of both.
         */
        $relativeRegexes = array(
            // url(xxx)
            '/
            # open url()
            url\(

                \s*

                # open path enclosure
                (?P<quotes>["\'])?

                    # fetch path
                    (?P<path>.+?)

                # close path enclosure
                (?(quotes)(?P=quotes))

                \s*

            # close url()
            \)

            /ix',

            // @import "xxx"
            '/
            # import statement
            @import

            # whitespace
            \s+

                # we don\'t have to check for @import url(), because the
                # condition above will already catch these

                # open path enclosure
                (?P<quotes>["\'])

                    # fetch path
                    (?P<path>.+?)

                # close path enclosure
                (?P=quotes)

            /ix',
        );

        // find all relative urls in css
        $matches = array();
        foreach ($relativeRegexes as $relativeRegex) {
            if (preg_match_all($relativeRegex, $content, $regexMatches, PREG_SET_ORDER)) {
                $matches = array_merge($matches, $regexMatches);
            }
        }

        $search = array();
        $replace = array();

        // loop all urls
        foreach ($matches as $match) {
            // determine if it's a url() or an @import match
            $type = (strpos($match[0], '@import') === 0 ? 'import' : 'url');

            $url = $match['path'];
            if ($this->canImportByPath($url)) {
                // attempting to interpret GET-params makes no sense, so let's discard them for awhile
                $params = strrchr($url, '?');
                $url = $params ? substr($url, 0, -strlen($params)) : $url;

                // fix relative url
                $url = $converter->convert($url);

                // now that the path has been converted, re-apply GET-params
                $url .= $params;
            }

            /*
             * Urls with control characters above 0x7e should be quoted.
             * According to Mozilla's parser, whitespace is only allowed at the
             * end of unquoted urls.
             * Urls with `)` (as could happen with data: uris) should also be
             * quoted to avoid being confused for the url() closing parentheses.
             * And urls with a # have also been reported to cause issues.
             * Urls with quotes inside should also remain escaped.
             *
             * @see https://developer.mozilla.org/nl/docs/Web/CSS/url#The_url()_functional_notation
             * @see https://hg.mozilla.org/mozilla-central/rev/14abca4e7378
             * @see https://github.com/matthiasmullie/minify/issues/193
             */
            $url = trim($url);
            if (preg_match('/[\s\)\'"#\x{7f}-\x{9f}]/u', $url)) {
                $url = $match['quotes'] . $url . $match['quotes'];
            }

            // build replacement
            $search[] = $match[0];
            if ($type === 'url') {
                $replace[] = 'url('.$url.')';
            } elseif ($type === 'import') {
                $replace[] = '@import "'.$url.'"';
            }
        }

        // replace urls
        return str_replace($search, $replace, $content);
    }

    /**
     * Shorthand hex color codes.
     * #FF0000 -> #F00.
     *
     * @param string $content The CSS content to shorten the hex color codes for
     *
     * @return string
     */
    protected function shortenHex($content)
    {
        $content = preg_replace('/(?<=[: ])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?=[; }])/i', '#$1$2$3', $content);

        // we can shorten some even more by replacing them with their color name
        $colors = array(
            '#F0FFFF' => 'azure',
            '#F5F5DC' => 'beige',
            '#A52A2A' => 'brown',
            '#FF7F50' => 'coral',
            '#FFD700' => 'gold',
            '#808080' => 'gray',
            '#008000' => 'green',
            '#4B0082' => 'indigo',
            '#FFFFF0' => 'ivory',
            '#F0E68C' => 'khaki',
            '#FAF0E6' => 'linen',
            '#800000' => 'maroon',
            '#000080' => 'navy',
            '#808000' => 'olive',
            '#CD853F' => 'peru',
            '#FFC0CB' => 'pink',
            '#DDA0DD' => 'plum',
            '#800080' => 'purple',
            '#F00' => 'red',
            '#FA8072' => 'salmon',
            '#A0522D' => 'sienna',
            '#C0C0C0' => 'silver',
            '#FFFAFA' => 'snow',
            '#D2B48C' => 'tan',
            '#FF6347' => 'tomato',
            '#EE82EE' => 'violet',
            '#F5DEB3' => 'wheat',
        );

        return preg_replace_callback(
            '/(?<=[: ])('.implode(array_keys($colors), '|').')(?=[; }])/i',
            function ($match) use ($colors) {
                return $colors[strtoupper($match[0])];
            },
            $content
        );
    }

    /**
     * Shorten CSS font weights.
     *
     * @param string $content The CSS content to shorten the font weights for
     *
     * @return string
     */
    protected function shortenFontWeights($content)
    {
        $weights = array(
            'normal' => 400,
            'bold' => 700,
        );

        $callback = function ($match) use ($weights) {
            return $match[1].$weights[$match[2]];
        };

        return preg_replace_callback('/(font-weight\s*:\s*)('.implode('|', array_keys($weights)).')(?=[;}])/', $callback, $content);
    }

    /**
     * Shorthand 0 values to plain 0, instead of e.g. -0em.
     *
     * @param string $content The CSS content to shorten the zero values for
     *
     * @return string
     */
    protected function shortenZeroes($content)
    {
        // we don't want to strip units in `calc()` expressions:
        // `5px - 0px` is valid, but `5px - 0` is not
        // `10px * 0` is valid (equates to 0), and so is `10 * 0px`, but
        // `10 * 0` is invalid
        // best to just leave `calc()`s alone, even if they could be optimized
        // (which is a whole other undertaking, where units & order of
        // operations all need to be considered...)
        $calcs = $this->findCalcs($content);
        $content = str_replace($calcs, array_keys($calcs), $content);

        // reusable bits of code throughout these regexes:
        // before & after are used to make sure we don't match lose unintended
        // 0-like values (e.g. in #000, or in http://url/1.0)
        // units can be stripped from 0 values, or used to recognize non 0
        // values (where wa may be able to strip a .0 suffix)
        $before = '(?<=[:(, ])';
        $after = '(?=[ ,);}])';
        $units = '(em|ex|%|px|cm|mm|in|pt|pc|ch|rem|vh|vw|vmin|vmax|vm)';

        // strip units after zeroes (0px -> 0)
        // NOTE: it should be safe to remove all units for a 0 value, but in
        // practice, Webkit (especially Safari) seems to stumble over at least
        // 0%, potentially other units as well. Only stripping 'px' for now.
        // @see https://github.com/matthiasmullie/minify/issues/60
        $content = preg_replace('/'.$before.'(-?0*(\.0+)?)(?<=0)px'.$after.'/', '\\1', $content);

        // strip 0-digits (.0 -> 0)
        $content = preg_replace('/'.$before.'\.0+'.$units.'?'.$after.'/', '0\\1', $content);
        // strip trailing 0: 50.10 -> 50.1, 50.10px -> 50.1px
        $content = preg_replace('/'.$before.'(-?[0-9]+\.[0-9]+)0+'.$units.'?'.$after.'/', '\\1\\2', $content);
        // strip trailing 0: 50.00 -> 50, 50.00px -> 50px
        $content = preg_replace('/'.$before.'(-?[0-9]+)\.0+'.$units.'?'.$after.'/', '\\1\\2', $content);
        // strip leading 0: 0.1 -> .1, 01.1 -> 1.1
        $content = preg_replace('/'.$before.'(-?)0+([0-9]*\.[0-9]+)'.$units.'?'.$after.'/', '\\1\\2\\3', $content);

        // strip negative zeroes (-0 -> 0) & truncate zeroes (00 -> 0)
        $content = preg_replace('/'.$before.'-?0+'.$units.'?'.$after.'/', '0\\1', $content);

        // IE doesn't seem to understand a unitless flex-basis value (correct -
        // it goes against the spec), so let's add it in again (make it `%`,
        // which is only 1 char: 0%, 0px, 0 anything, it's all just the same)
        // @see https://developer.mozilla.org/nl/docs/Web/CSS/flex
        $content = preg_replace('/flex:([0-9]+\s[0-9]+\s)0([;\}])/', 'flex:${1}0%${2}', $content);
        $content = preg_replace('/flex-basis:0([;\}])/', 'flex-basis:0%${1}', $content);

        // restore `calc()` expressions
        $content = str_replace(array_keys($calcs), $calcs, $content);

        return $content;
    }

    /**
     * Strip empty tags from source code.
     *
     * @param string $content
     *
     * @return string
     */
    protected function stripEmptyTags($content)
    {
        $content = preg_replace('/(?<=^)[^\{\};]+\{\s*\}/', '', $content);
        $content = preg_replace('/(?<=(\}|;))[^\{\};]+\{\s*\}/', '', $content);

        return $content;
    }

    /**
     * Strip comments from source code.
     */
    protected function stripComments()
    {
        $this->registerPattern('/\/\*.*?\*\//s', '');
    }

    /**
     * Strip whitespace.
     *
     * @param string $content The CSS content to strip the whitespace for
     *
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // remove leading & trailing whitespace
        $content = preg_replace('/^\s*/m', '', $content);
        $content = preg_replace('/\s*$/m', '', $content);

        // replace newlines with a single space
        $content = preg_replace('/\s+/', ' ', $content);

        // remove whitespace around meta characters
        // inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex
        $content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
        $content = preg_replace('/([\[(:])\s+/', '$1', $content);
        $content = preg_replace('/\s+([\]\)])/', '$1', $content);
        $content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);

        // whitespace around + and - can only be stripped inside some pseudo-
        // classes, like `:nth-child(3+2n)`
        // not in things like `calc(3px + 2px)`, shorthands like `3px -2px`, or
        // selectors like `div.weird- p`
        $pseudos = array('nth-child', 'nth-last-child', 'nth-last-of-type', 'nth-of-type');
        $content = preg_replace('/:('.implode('|', $pseudos).')\(\s*([+-]?)\s*(.+?)\s*([+-]?)\s*(.*?)\s*\)/', ':$1($2$3$4$5)', $content);

        // remove semicolon/whitespace followed by closing bracket
        $content = str_replace(';}', '}', $content);

        return trim($content);
    }

    /**
     * Find all `calc()` occurrences.
     *
     * @param string $content The CSS content to find `calc()`s in.
     *
     * @return string[]
     */
    protected function findCalcs($content)
    {
        $results = array();
        preg_match_all('/calc(\(.+?)(?=$|;|calc\()/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $length = strlen($match[1]);
            $expr = '';
            $opened = 0;

            for ($i = 0; $i < $length; $i++) {
                $char = $match[1][$i];
                $expr .= $char;
                if ($char === '(') {
                    $opened++;
                } elseif ($char === ')' && --$opened === 0) {
                    break;
                }
            }

            $results['calc('.count($results).')'] = 'calc'.$expr;
        }

        return $results;
    }

    /**
     * Check if file is small enough to be imported.
     *
     * @param string $path The path to the file
     *
     * @return bool
     */
    protected function canImportBySize($path)
    {
        return ($size = @filesize($path)) && $size <= $this->maxImportSize * 1024;
    }

    /**
     * Check if file a file can be imported, going by the path.
     *
     * @param string $path
     *
     * @return bool
     */
    protected function canImportByPath($path)
    {
        return preg_match('/^(data:|https?:|\\/)/', $path) === 0;
    }

    /**
     * Return a converter to update relative paths to be relative to the new
     * destination.
     *
     * @param string $source
     * @param string $target
     *
     * @return ConverterInterface
     */
    protected function getPathConverter($source, $target)
    {
        return new Converter($source, $target);
    }
}
