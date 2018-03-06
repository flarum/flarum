### 1.10.0 (2015-07-22)

 * Added trimLeft, trimRight
 * Added support for unicode whitespace to trim
 * Added delimit
 * Added indexOf and indexOfLast
 * Added htmlEncode and htmlDecode
 * Added "Ç" in toAscii()

### 1.9.0 (2015-02-09)

 * Added hasUpperCase and hasLowerCase
 * Added $removeUnsupported parameter to toAscii()
 * Improved toAscii support with additional Unicode spaces, Vietnamese chars,
   and numerous other characters
 * Separated the charsArray from toAscii as a protected method that may be
   extended by inheriting classes
 * Chars array is cached for better performance

### 1.8.1 (2015-01-08)

 * Optimized chars()
 * Added "ä Ä Ö Ü"" in toAscii()
 * Added support for Unicode spaces in toAscii()
 * Replaced instances of self::create() with static::create()
 * Added missing test cases for safeTruncate() and longestCommonSuffix()
 * Updated Stringy\create() to avoid collision when it already exists

### 1.8.0 (2015-01-03)

 * Listed ext-mbstring in composer.json
 * Added Stringy\create function for PHP 5.6

### 1.7.0 (2014-10-14)

 * Added containsAll and containsAny
 * Light cleanup

### 1.6.0 (2014-09-14)

 * Added toTitleCase

### 1.5.2 (2014-07-09)

 * Announced support for HHVM

### 1.5.1 (2014-04-19)

  * Fixed toAscii() failing to remove remaining non-ascii characters
  * Updated slugify() to treat dash and underscore as delimiters by default
  * Updated slugify() to remove leading and trailing delimiter, if present

### 1.5.0 (2014-03-19)

  * Made both str and encoding protected, giving property access to subclasses
  * Added getEncoding()
  * Fixed isJSON() giving false negatives
  * Cleaned up and simplified: replace(), collapseWhitespace(), underscored(),
    dasherize(), pad(), padLeft(), padRight() and padBoth()
  * Fixed handling consecutive invalid chars in slugify()
  * Removed conflicting hard sign transliteration in toAscii()

### 1.4.0 (2014-02-12)

  * Implemented the IteratorAggregate interface, added chars()
  * Renamed count() to countSubstr()
  * Updated count() to implement Countable interface
  * Implemented the ArrayAccess interface with positive and negative indices
  * Switched from PSR-0 to PSR-4 autoloading

### 1.3.0 (2013-12-16)

  * Additional Bulgarian support for toAscii
  * str property made private
  * Constructor casts first argument to string
  * Constructor throws an InvalidArgumentException when given an array
  * Constructor throws an InvalidArgumentException when given an object without
    a __toString method

### 1.2.2 (2013-12-04)

  * Updated create function to use late static binding
  * Added optional $replacement param to slugify

### 1.2.1 (2013-10-11)

  * Cleaned up tests
  * Added homepage to composer.json

### 1.2.0 (2013-09-15)

  * Fixed pad's use of InvalidArgumentException
  * Fixed replace(). It now correctly treats regex special chars as normal chars
  * Added additional Cyrillic letters to toAscii
  * Added $caseSensitive to contains() and count()
  * Added toLowerCase()
  * Added toUpperCase()
  * Added regexReplace()

### 1.1.0 (2013-08-31)

  * Fix for collapseWhitespace()
  * Added isHexadecimal()
  * Added constructor to Stringy\Stringy
  * Added isSerialized()
  * Added isJson()

### 1.0.0 (2013-08-1)

  * 1.0.0 release
  * Added test coverage for Stringy::create and method chaining
  * Added tests for returned type
  * Fixed StaticStringy::replace(). It was returning a Stringy object instead of string
  * Renamed standardize() to the more appropriate toAscii()
  * Cleaned up comments and README

### 1.0.0-rc.1 (2013-07-28)

  * Release candidate
