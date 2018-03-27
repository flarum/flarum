<?php

namespace Stringy;

class StaticStringy
{
    /**
     * Returns an array consisting of the characters in the string.
     *
     * @param  string $str      String for which to return the chars
     * @param  string $encoding The character encoding
     * @return array  An array of string chars
     */
    public static function chars($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->chars();
    }

    /**
     * Converts the first character of the supplied string to upper case.
     *
     * @param  string $str      String to modify
     * @param  string $encoding The character encoding
     * @return string String with the first character being upper case
     */
    public static function upperCaseFirst($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->upperCaseFirst();
    }

    /**
     * Converts the first character of the supplied string to lower case.
     *
     * @param  string $str      String to modify
     * @param  string $encoding The character encoding
     * @return string String with the first character being lower case
     */
    public static function lowerCaseFirst($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->lowerCaseFirst();
    }

    /**
     * Returns a camelCase version of the string. Trims surrounding spaces,
     * capitalizes letters following digits, spaces, dashes and underscores,
     * and removes spaces, dashes, as well as underscores.
     *
     * @param  string $str      String to convert to camelCase
     * @param  string $encoding The character encoding
     * @return string String in camelCase
     */
    public static function camelize($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->camelize();
    }

    /**
     * Returns an UpperCamelCase version of the supplied string. It trims
     * surrounding spaces, capitalizes letters following digits, spaces, dashes
     * and underscores, and removes spaces, dashes, underscores.
     *
     * @param  string $str      String to convert to UpperCamelCase
     * @param  string $encoding The character encoding
     * @return string String in UpperCamelCase
     */
    public static function upperCamelize($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->upperCamelize();
    }

    /**
     * Returns a lowercase and trimmed string separated by dashes. Dashes are
     * inserted before uppercase characters (with the exception of the first
     * character of the string), and in place of spaces as well as underscores.
     *
     * @param  string $str      String to convert
     * @param  string $encoding The character encoding
     * @return string Dasherized string
     */
    public static function dasherize($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->dasherize();
    }

    /**
     * Returns a lowercase and trimmed string separated by underscores.
     * Underscores are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces as well as
     * dashes.
     *
     * @param  string  $str      String to convert
     * @param  string  $encoding The character encoding
     * @return string  Underscored string
     */
    public static function underscored($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->underscored();
    }

    /**
     * Returns a lowercase and trimmed string separated by the given delimiter.
     * Delimiters are inserted before uppercase characters (with the exception
     * of the first character of the string), and in place of spaces, dashes,
     * and underscores. Alpha delimiters are not converted to lowercase.
     *
     * @param  string  $str       String to convert
     * @param  string  $delimiter Sequence used to separate parts of the string
     * @param  string  $encoding  The character encoding
     * @return string  String with delimiter
     */
    public static function delimit($str, $delimiter, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->delimit($delimiter);
    }

    /**
     * Returns a case swapped version of the string.
     *
     * @param  string $str      String to swap case
     * @param  string $encoding The character encoding
     * @return string String with each character's case swapped
     */
    public static function swapCase($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->swapCase();
    }

    /**
     * Returns a trimmed string with the first letter of each word capitalized.
     * Ignores the case of other letters, preserving any acronyms. Also accepts
     * an array, $ignore, allowing you to list words not to be capitalized.
     *
     * @param  string $str      String to titleize
     * @param  string $encoding The character encoding
     * @param  array  $ignore   An array of words not to capitalize
     * @return string Titleized string
     */
    public static function titleize($str, $ignore = null, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->titleize($ignore);
    }

    /**
     * Capitalizes the first word of the string, replaces underscores with
     * spaces, and strips '_id'.
     *
     * @param  string $str      String to humanize
     * @param  string $encoding The character encoding
     * @return string A humanized string
     */
    public static function humanize($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->humanize();
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * @param  string $str String to remove special chars
     * @return string String with those characters removed
     */
    public static function tidy($str)
    {
        return (string) Stringy::create($str)->tidy();
    }

    /**
     * Trims the string and replaces consecutive whitespace characters with a
     * single space. This includes tabs and newline characters, as well as
     * multibyte whitespace such as the thin space and ideographic space.
     *
     * @param  string $str      The string to cleanup whitespace
     * @param  string $encoding The character encoding
     * @return string The trimmed string with condensed whitespace
     */
    public static function collapseWhitespace($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->collapseWhitespace();
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * @param  string $str               A string with non-ASCII characters
     * @param  bool   $removeUnsupported Whether or not to remove the
     *                                   unsupported characters
     * @return string A string containing only ASCII characters
     */
    public static function toAscii($str, $removeUnsupported = true)
    {
        return (string) Stringy::create($str)->toAscii($removeUnsupported);
    }

    /**
     * Pads the string to a given length with $padStr. If length is less than
     * or equal to the length of the string, no padding takes places. The
     * default string used for padding is a space, and the default type (one of
     * 'left', 'right', 'both') is 'right'. Throws an InvalidArgumentException
     * if $padType isn't one of those 3 values.
     *
     * @param  string $str      String to pad
     * @param  int    $length   Desired string length after padding
     * @param  string $padStr   String used to pad, defaults to space
     * @param  string $padType  One of 'left', 'right', 'both'
     * @param  string $encoding The character encoding
     * @return string The padded string
     * @throws \InvalidArgumentException If $padType isn't one of 'right',
     *         'left' or 'both'
     */
    public static function pad($str, $length, $padStr = ' ', $padType = 'right',
                               $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->pad($length, $padStr, $padType);
    }

    /**
     * Returns a new string of a given length such that the beginning of the
     * string is padded. Alias for pad() with a $padType of 'left'.
     *
     * @param  string $str      String to pad
     * @param  int    $length   Desired string length after padding
     * @param  string $padStr   String used to pad, defaults to space
     * @param  string $encoding The character encoding
     * @return string The padded string
     */
    public static function padLeft($str, $length, $padStr = ' ', $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->padLeft($length, $padStr);
    }

    /**
     * Returns a new string of a given length such that the end of the string
     * is padded. Alias for pad() with a $padType of 'right'.
     *
     * @param  string $str      String to pad
     * @param  int    $length   Desired string length after padding
     * @param  string $padStr   String used to pad, defaults to space
     * @param  string $encoding The character encoding
     * @return string The padded string
     */
    public static function padRight($str, $length, $padStr = ' ', $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->padRight($length, $padStr);
    }

    /**
     * Returns a new string of a given length such that both sides of the
     * string are padded. Alias for pad() with a $padType of 'both'.
     *
     * @param  string $str      String to pad
     * @param  int    $length   Desired string length after padding
     * @param  string $padStr   String used to pad, defaults to space
     * @param  string $encoding The character encoding
     * @return string The padded string
     */
    public static function padBoth($str, $length, $padStr = ' ', $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->padBoth($length, $padStr);
    }

    /**
     * Returns true if the string begins with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $str           String to check the start of
     * @param  string $substring     The substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @param  string $encoding      The character encoding
     * @return bool   Whether or not $str starts with $substring
     */
    public static function startsWith($str, $substring, $caseSensitive = true,
                                      $encoding = null)
    {
        return Stringy::create($str, $encoding)
            ->startsWith($substring, $caseSensitive);
    }

    /**
     * Returns true if the string ends with $substring, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $str           String to check the end of
     * @param  string $substring     The substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @param  string $encoding      The character encoding
     * @return bool   Whether or not $str ends with $substring
     */
    public static function endsWith($str, $substring, $caseSensitive = true,
                                    $encoding = null)
    {
        return Stringy::create($str, $encoding)
            ->endsWith($substring, $caseSensitive);
    }

    /**
     * Converts each tab in the string to some number of spaces, as defined by
     * $tabLength. By default, each tab is converted to 4 consecutive spaces.
     *
     * @param  string $str       String to convert tabs to spaces
     * @param  int    $tabLength Number of spaces to replace each tab with
     * @return string String with tabs switched to spaces
     */
    public static function toSpaces($str, $tabLength = 4)
    {
        return (string) Stringy::create($str)->toSpaces($tabLength);
    }

    /**
     * Converts each occurrence of some consecutive number of spaces, as
     * defined by $tabLength, to a tab. By default, each 4 consecutive spaces
     * are converted to a tab.
     *
     * @param  string $str       String to convert spaces to tabs
     * @param  int    $tabLength Number of spaces to replace with a tab
     * @return string String with spaces switched to tabs
     */
    public static function toTabs($str, $tabLength = 4)
    {
        return (string) Stringy::create($str)->toTabs($tabLength);
    }

    /**
     * Converts all characters in the string to lowercase. An alias for PHP's
     * mb_strtolower().
     *
     * @param  string $str      String to convert case
     * @param  string $encoding The character encoding
     * @return string The lowercase string
     */
    public static function toLowerCase($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->toLowerCase();
    }

    /**
     * Converts the first character of each word in the string to uppercase.
     *
     * @param  string $str      String to convert case
     * @param  string $encoding The character encoding
     * @return string The title-cased string
     */
    public static function toTitleCase($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->toTitleCase();
    }

    /**
     * Converts all characters in the string to uppercase. An alias for PHP's
     * mb_strtoupper().
     *
     * @param  string $str      String to convert case
     * @param  string $encoding The character encoding
     * @return string The uppercase string
     */
    public static function toUpperCase($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->toUpperCase();
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $replacement. The replacement defaults to a single dash, and the string
     * is also converted to lowercase.
     *
     * @param  string $str         Text to transform into an URL slug
     * @param  string $replacement The string used to replace whitespace
     * @return string The corresponding URL slug
     */
    public static function slugify($str, $replacement = '-')
    {
        return (string) Stringy::create($str)->slugify($replacement);
    }

    /**
     * Returns true if the string contains $needle, false otherwise. By default,
     * the comparison is case-sensitive, but can be made insensitive by setting
     * $caseSensitive to false.
     *
     * @param  string $haystack      String being checked
     * @param  string $needle        Substring to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @param  string $encoding      The character encoding
     * @return bool   Whether or not $haystack contains $needle
     */
    public static function contains($haystack, $needle, $caseSensitive = true,
                                    $encoding = null)
    {
        return Stringy::create($haystack, $encoding)
            ->contains($needle, $caseSensitive);
    }

    /**
     * Returns true if the string contains any $needles, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $haystack      String being checked
     * @param  array  $needles       Substrings to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @param  string $encoding      The character encoding
     * @return bool   Whether or not $haystack contains any $needles
     */
    public static function containsAny($haystack, $needles,
                                       $caseSensitive = true, $encoding = null)
    {
        return Stringy::create($haystack, $encoding)
            ->containsAny($needles, $caseSensitive);
    }

    /**
     * Returns true if the string contains all $needles, false otherwise. By
     * default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $haystack      String being checked
     * @param  array  $needles       Substrings to look for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @param  string $encoding      The character encoding
     * @return bool   Whether or not $haystack contains all $needles
     */
    public static function containsAll($haystack, $needles,
                                       $caseSensitive = true, $encoding = null)
    {
        return Stringy::create($haystack, $encoding)
            ->containsAll($needles, $caseSensitive);
    }

    /**
     * Returns the index of the first occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * @param  string   $haystack String to search
     * @param  string   $needle   Substring to look for
     * @param  int      $offset   Offset from which to search
     * @return int|bool The occurrence's index if found, otherwise false
     */
    public static function indexOf($haystack, $needle, $offset = 0,
                                   $encoding = null)
    {
        return Stringy::create($haystack, $encoding)
            ->indexOf($needle, $offset);
    }

    /**
     * Returns the index of the last occurrence of $needle in the string,
     * and false if not found. Accepts an optional offset from which to begin
     * the search.
     *
     * @param  string   $haystack String to search
     * @param  string   $needle   Substring to look for
     * @param  int      $offset   Offset from which to search
     * @return int|bool The last occurrence's index if found, otherwise false
     */
    public static function indexOfLast($haystack, $needle, $offset = 0,
                                       $encoding = null)
    {
        return Stringy::create($haystack, $encoding)
            ->indexOfLast($needle, $offset);
    }

    /**
     * Surrounds a string with the given substring.
     *
     * @param  string $str       The string to surround
     * @param  string $substring The substring to add to both sides
     * @return string The string with the substring prepended and appended
     */
    public static function surround($str, $substring)
    {
        return (string) Stringy::create($str)->surround($substring);
    }

    /**
     * Inserts $substring into the string at the $index provided.
     *
     * @param  string $str       String to insert into
     * @param  string $substring String to be inserted
     * @param  int    $index     The index at which to insert the substring
     * @param  string $encoding  The character encoding
     * @return string The resulting string after the insertion
     */
    public static function insert($str, $substring, $index, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->insert($substring, $index);
    }

    /**
     * Truncates the string to a given length. If $substring is provided, and
     * truncating occurs, the string is further truncated so that the substring
     * may be appended without exceeding the desired length.
     *
     * @param  string $str       String to truncate
     * @param  int    $length    Desired length of the truncated string
     * @param  string $substring The substring to append if it can fit
     * @param  string $encoding  The character encoding
     * @return string The resulting string after truncating
     */
    public static function truncate($str, $length, $substring = '',
                                    $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->truncate($length, $substring);
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not
     * split words. If $substring is provided, and truncating occurs, the
     * string is further truncated so that the substring may be appended without
     * exceeding the desired length.
     *
     * @param  string $str       String to truncate
     * @param  int    $length    Desired length of the truncated string
     * @param  string $substring The substring to append if it can fit
     * @param  string $encoding  The character encoding
     * @return string The resulting string after truncating
     */
    public static function safeTruncate($str, $length, $substring = '',
                                        $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->safeTruncate($length, $substring);
    }

    /**
     * Returns a reversed string. A multibyte version of strrev().
     *
     * @param  string $str      String to reverse
     * @param  string $encoding The character encoding
     * @return string The reversed string
     */
    public static function reverse($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->reverse();
    }

    /**
     * A multibyte str_shuffle() function. It returns a string with its
     * characters in random order.
     *
     * @param  string $str      String to shuffle
     * @param  string $encoding The character encoding
     * @return string The shuffled string
     */
    public static function shuffle($str, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->shuffle();
    }

    /**
     * Returns a string with whitespace removed from the start and end of the
     * string. Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param  string $str      String to trim
     * @param  string $chars    Optional string of characters to strip
     * @param  string $encoding The character encoding
     * @return string Trimmed $str
     */
    public static function trim($str, $chars = null, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->trim($chars);
    }

    /**
     * Returns a string with whitespace removed from the start of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param  string $str      String to trim
     * @param  string $chars    Optional string of characters to strip
     * @param  string $encoding The character encoding
     * @return string Trimmed $str
     */
    public static function trimLeft($str, $chars = null, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->trimLeft($chars);
    }

    /**
     * Returns a string with whitespace removed from the end of the string.
     * Supports the removal of unicode whitespace. Accepts an optional
     * string of characters to strip instead of the defaults.
     *
     * @param  string $str      String to trim
     * @param  string $chars    Optional string of characters to strip
     * @param  string $encoding The character encoding
     * @return string Trimmed $str
     */
    public static function trimRight($str, $chars = null, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->trimRight($chars);
    }

    /**
     * Returns the longest common prefix between the string and $otherStr.
     *
     * @param  string $str      First string for comparison
     * @param  string $otherStr Second string for comparison
     * @param  string $encoding The character encoding
     * @return string The longest common prefix
     */
    public static function longestCommonPrefix($str, $otherStr, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->longestCommonPrefix($otherStr);
    }

    /**
     * Returns the longest common suffix between the string and $otherStr.
     *
     * @param  string $str      First string for comparison
     * @param  string $otherStr Second string for comparison
     * @param  string $encoding The character encoding
     * @return string The longest common suffix
     */
    public static function longestCommonSuffix($str, $otherStr, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->longestCommonSuffix($otherStr);
    }

    /**
     * Returns the longest common substring between the string and $otherStr.
     * In the case of ties, it returns that which occurs first.
     *
     * @param  string $str      First string for comparison
     * @param  string $otherStr Second string for comparison
     * @param  string $encoding The character encoding
     * @return string The longest common substring
     */
    public static function longestCommonSubstring($str, $otherStr,
                                                  $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->longestCommonSubstring($otherStr);
    }

    /**
     * Returns the length of the string. An alias for PHP's mb_strlen() function.
     *
     * @param  string $str      The string to get the length of
     * @param  string $encoding The character encoding
     * @return int    The number of characters in $str given the encoding
     */
    public static function length($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->length();
    }

    /**
     * Returns the substring beginning at $start with the specified $length.
     * It differs from the mb_substr() function in that providing a $length of
     * null will return the rest of the string, rather than an empty string.
     *
     * @param  string $str      The string to get the length of
     * @param  int    $start    Position of the first character to use
     * @param  int    $length   Maximum number of characters used
     * @param  string $encoding The character encoding
     * @return string The substring of $str
     */
    public static function substr($str, $start, $length = null, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->substr($start, $length);
    }

    /**
     * Returns the character at $index, with indexes starting at 0.
     *
     * @param  string $str      The string from which to get the char
     * @param  int    $index    Position of the character
     * @param  string $encoding The character encoding
     * @return string The character at $index
     */
    public static function at($str, $index, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->at($index);
    }

    /**
     * Returns the first $n characters of the string.
     *
     * @param  string $str      The string from which to get the substring
     * @param  int    $n        Number of chars to retrieve from the start
     * @param  string $encoding The character encoding
     * @return string The first $n characters
     */
    public static function first($str, $n, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->first($n);
    }

    /**
     * Returns the last $n characters of the string.
     *
     * @param  string $str      The string from which to get the substring
     * @param  int    $n        Number of chars to retrieve from the end
     * @param  string $encoding The character encoding
     * @return string The last $n characters
     */
    public static function last($str, $n, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->last($n);
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's
     * prepended.
     *
     * @param  string $str       The string to modify
     * @param  string $substring The substring to add if not present
     * @param  string $encoding  The character encoding
     * @return string The string prefixed by the $substring
     */
    public static function ensureLeft($str, $substring, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->ensureLeft($substring);
    }

    /**
     * Ensures that the string begins with $substring. If it doesn't, it's
     * appended.
     *
     * @param  string $str       The string to modify
     * @param  string $substring The substring to add if not present
     * @param  string $encoding  The character encoding
     * @return string The string suffixed by the $substring
     */
    public static function ensureRight($str, $substring, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->ensureRight($substring);
    }

    /**
     * Returns a new string with the prefix $substring removed, if present.
     *
     * @param  string $str       String from which to remove the prefix
     * @param  string $substring The prefix to remove
     * @param  string $encoding  The character encoding
     * @return string The string without the prefix $substring
     */
    public static function removeLeft($str, $substring, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->removeLeft($substring);
    }

    /**
     * Returns a new string with the suffix $substring removed, if present.
     *
     * @param  string $str       String from which to remove the suffix
     * @param  string $substring The suffix to remove
     * @param  string $encoding  The character encoding
     * @return string The string without the suffix $substring
     */
    public static function removeRight($str, $substring, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->removeRight($substring);
    }

    /**
     * Returns true if the string contains a lower case char, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains a lower case character.
     */
    public static function hasLowerCase($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->hasLowerCase();
    }

    /**
     * Returns true if the string contains an upper case char, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains an upper case character.
     */
    public static function hasUpperCase($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->hasUpperCase();
    }

    /**
     * Returns true if the string contains only alphabetic chars, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains only alphabetic chars
     */
    public static function isAlpha($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isAlpha();
    }

    /**
     * Returns true if the string contains only alphabetic and numeric chars,
     * false otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains only alphanumeric chars
     */
    public static function isAlphanumeric($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isAlphanumeric();
    }

    /**
     * Returns true if the string contains only whitespace chars, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains only whitespace characters
     */
    public static function isBlank($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isBlank();
    }

    /**
     * Returns true if the string is JSON, false otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str is JSON
     */
    public static function isJson($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isJson();
    }

    /**
     * Returns true if the string contains only lower case chars, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains only lower case characters
     */
    public static function isLowerCase($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isLowerCase();
    }

    /**
     * Returns true if the string is serialized, false otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str is serialized
     */
    public static function isSerialized($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isSerialized();
    }

    /**
     * Returns true if the string contains only upper case chars, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains only upper case characters
     */
    public static function isUpperCase($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isUpperCase();
    }

    /**
     * Returns true if the string contains only hexadecimal chars, false
     * otherwise.
     *
     * @param  string $str      String to check
     * @param  string $encoding The character encoding
     * @return bool   Whether or not $str contains only hexadecimal characters
     */
    public static function isHexadecimal($str, $encoding = null)
    {
        return Stringy::create($str, $encoding)->isHexadecimal();
    }

    /**
     * Returns the number of occurrences of $substring in the given string.
     * By default, the comparison is case-sensitive, but can be made insensitive
     * by setting $caseSensitive to false.
     *
     * @param  string $str           The string to search through
     * @param  string $substring     The substring to search for
     * @param  bool   $caseSensitive Whether or not to enforce case-sensitivity
     * @param  string $encoding      The character encoding
     * @return int    The number of $substring occurrences
     */
    public static function countSubstr($str, $substring, $caseSensitive = true,
                                       $encoding = null)
    {
        return Stringy::create($str, $encoding)
            ->countSubstr($substring, $caseSensitive);
    }

    /**
     * Replaces all occurrences of $search in $str by $replacement.
     *
     * @param  string $str         The haystack to search through
     * @param  string $search      The needle to search for
     * @param  string $replacement The string to replace with
     * @param  string $encoding    The character encoding
     * @return string The resulting string after the replacements
     */
    public static function replace($str, $search, $replacement, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->replace($search, $replacement);
    }

    /**
     * Replaces all occurrences of $pattern in $str by $replacement. An alias
     * for mb_ereg_replace(). Note that the 'i' option with multibyte patterns
     * in mb_ereg_replace() requires PHP 5.4+. This is due to a lack of support
     * in the bundled version of Oniguruma in PHP 5.3.
     *
     * @param  string $str         The haystack to search through
     * @param  string $pattern     The regular expression pattern
     * @param  string $replacement The string to replace with
     * @param  string $options     Matching conditions to be used
     * @param  string $encoding    The character encoding
     * @return string The resulting string after the replacements
     */
    public static function regexReplace($str, $pattern, $replacement,
                                        $options = 'msr', $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)
            ->regexReplace($pattern, $replacement, $options, $encoding);
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * @param  string   $str   The string to encode.
     * @param  int|null $flags See http://php.net/manual/en/function.htmlentities.php
     * @param  string   $encoding    The character encoding
     * @return Stringy  Object with the resulting $str after being html encoded.
     */
    public static function htmlEncode($str, $flags = ENT_COMPAT, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->htmlEncode($flags);
    }

    /**
     * Convert all HTML entities to their applicable characters.
     *
     * @param  string   $str   The string to decode.
     * @param  int|null $flags See http://php.net/manual/en/function.html-entity-decode.php
     * @param  string   $encoding    The character encoding
     * @return Stringy  Object with the resulting $str after being html decoded.
     */
    public static function htmlDecode($str, $flags = ENT_COMPAT, $encoding = null)
    {
        return (string) Stringy::create($str, $encoding)->htmlDecode($flags);
    }
}
