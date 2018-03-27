![Stringy](http://danielstjules.com/github/stringy-logo.png)

A PHP string manipulation library with multibyte support. Offers both OO method
chaining and a procedural-style static wrapper. Tested and compatible with
PHP 5.3+ and HHVM. Inspired by underscore.string.js.

[![Build Status](https://api.travis-ci.org/danielstjules/Stringy.svg?branch=master)](https://travis-ci.org/danielstjules/Stringy)

* [Requiring/Loading](#requiringloading)
* [OO and Procedural](#oo-and-procedural)
* [Implemented Interfaces](#implemented-interfaces)
* [PHP 5.6 Creation](#php-56-creation)
* [Methods](#methods)
    * [at](#at)
    * [camelize](#camelize)
    * [chars](#chars)
    * [collapseWhitespace](#collapsewhitespace)
    * [contains](#contains)
    * [containsAll](#containsall)
    * [containsAny](#containsany)
    * [countSubstr](#countsubstr)
    * [create](#create)
    * [dasherize](#dasherize)
    * [delimit](#delimit)
    * [endsWith](#endswith)
    * [ensureLeft](#ensureleft)
    * [ensureRight](#ensureright)
    * [first](#first)
    * [getEncoding](#getencoding)
    * [hasLowerCase](#haslowercase)
    * [hasUpperCase](#hasuppercase)
    * [htmlDecode](#htmldecode)
    * [htmlEncode](#htmlencode)
    * [humanize](#humanize)
    * [indexOf](#indexof)
    * [indexOfLast](#indexoflast)
    * [insert](#insert)
    * [isAlpha](#isalpha)
    * [isAlphanumeric](#isalphanumeric)
    * [isBlank](#isblank)
    * [isHexadecimal](#ishexadecimal)
    * [isJson](#isjson)
    * [isLowerCase](#islowercase)
    * [isSerialized](#isserialized)
    * [isUpperCase](#isuppercase)
    * [last](#last)
    * [length](#length)
    * [longestCommonPrefix](#longestcommonprefix)
    * [longestCommonSuffix](#longestcommonsuffix)
    * [longestCommonSubstring](#longestcommonsubstring)
    * [lowerCaseFirst](#lowercasefirst)
    * [pad](#pad)
    * [padBoth](#padboth)
    * [padLeft](#padleft)
    * [padRight](#padright)
    * [regexReplace](#regexreplace)
    * [removeLeft](#removeleft)
    * [removeRight](#removeright)
    * [replace](#replace)
    * [reverse](#reverse)
    * [safeTruncate](#safetruncate)
    * [shuffle](#shuffle)
    * [slugify](#slugify)
    * [startsWith](#startswith)
    * [substr](#substr)
    * [surround](#surround)
    * [swapCase](#swapcase)
    * [tidy](#tidy)
    * [titleize](#titleize)
    * [toAscii](#toascii)
    * [toLowerCase](#tolowercase)
    * [toSpaces](#tospaces)
    * [toTabs](#totabs)
    * [toTitleCase](#totitlecase)
    * [toUpperCase](#touppercase)
    * [trim](#trim)
    * [trimLeft](#trimLeft)
    * [trimRight](#trimRight)
    * [truncate](#truncate)
    * [underscored](#underscored)
    * [upperCamelize](#uppercamelize)
    * [upperCaseFirst](#uppercasefirst)
* [Links](#links)
* [Tests](#tests)
* [License](#license)

## Requiring/Loading

If you're using Composer to manage dependencies, you can include the following
in your composer.json file:

```json
{
    "require": {
        "danielstjules/stringy": "~1.10"
    }
}
```

Then, after running `composer update` or `php composer.phar update`, you can
load the class using Composer's autoloading:

```php
require 'vendor/autoload.php';
```

Otherwise, you can simply require the file directly:

```php
require_once 'path/to/Stringy/src/Stringy.php';
// or
require_once 'path/to/Stringy/src/StaticStringy.php';
```

And in either case, I'd suggest using an alias.

```php
use Stringy\Stringy as S;
// or
use Stringy\StaticStringy as S;
```

## OO and Procedural

The library offers both OO method chaining with `Stringy\Stringy`, as well as
procedural-style static method calls with `Stringy\StaticStringy`. An example
of the former is the following:

```php
use Stringy\Stringy as S;
echo S::create('Fòô     Bàř', 'UTF-8')->collapseWhitespace()->swapCase();  // 'fÒÔ bÀŘ'
```

`Stringy\Stringy` has a __toString() method, which returns the current string
when the object is used in a string context, ie:
`(string) S::create('foo')  // 'foo'`

Using the static wrapper, an alternative is the following:

```php
use Stringy\StaticStringy as S;
$string = S::collapseWhitespace('Fòô     Bàř', 'UTF-8');
echo S::swapCase($string, 'UTF-8');  // 'fÒÔ bÀŘ'
```

## Implemented Interfaces

`Stringy\Stringy` implements the `IteratorAggregate` interface, meaning that
`foreach` can be used with an instance of the class:

``` php
$stringy = S::create('Fòô Bàř', 'UTF-8');
foreach ($stringy as $char) {
    echo $char;
}
// 'Fòô Bàř'
```

It implements the `Countable` interface, enabling the use of `count()` to
retrieve the number of characters in the string:

``` php
$stringy = S::create('Fòô', 'UTF-8');
count($stringy);  // 3
```

Furthermore, the `ArrayAccess` interface has been implemented. As a result,
`isset()` can be used to check if a character at a specific index exists. And
since `Stringy\Stringy` is immutable, any call to `offsetSet` or `offsetUnset`
will throw an exception. `offsetGet` has been implemented, however, and accepts
both positive and negative indexes. Invalid indexes result in an
`OutOfBoundsException`.

``` php
$stringy = S::create('Bàř', 'UTF-8');
echo $stringy[2];     // 'ř'
echo $stringy[-2];    // 'à'
isset($stringy[-4]);  // false

$stringy[3];          // OutOfBoundsException
$stringy[2] = 'a';    // Exception
```

## PHP 5.6 Creation

As of PHP 5.6, [`use function`](https://wiki.php.net/rfc/use_function) is
available for importing functions. Stringy exposes a namespaced function,
`Stringy\create`, which emits the same behaviour as `Stringy\Stringy::create()`.
If running PHP 5.6, or another runtime that supports the `use function` syntax,
you can take advantage of an even simpler API as seen below:

``` php
use function Stringy\create as s;

// Instead of: S::create('Fòô     Bàř', 'UTF-8')
s('Fòô     Bàř', 'UTF-8')->collapseWhitespace()->swapCase();
```

## Methods

In the list below, any static method other than S::create refers to a method in
`Stringy\StaticStringy`. For all others, they're found in `Stringy\Stringy`.
Furthermore, all methods that return a Stringy object or string do not modify
the original. Stringy objects are immutable.

*Note: If `$encoding` is not given, it defaults to `mb_internal_encoding()`.*

#### at

$stringy->at(int $index)

S::at(int $index [, string $encoding ])

Returns the character at $index, with indexes starting at 0.

```php
S::create('fòô bàř', 'UTF-8')->at(6);
S::at('fòô bàř', 6, 'UTF-8');  // 'ř'
```

#### camelize

$stringy->camelize();

S::camelize(string $str [, string $encoding ])

Returns a camelCase version of the string. Trims surrounding spaces,
capitalizes letters following digits, spaces, dashes and underscores,
and removes spaces, dashes, as well as underscores.

```php
S::create('Camel-Case')->camelize();
S::camelize('Camel-Case');  // 'camelCase'
```

#### chars

$stringy->chars();

S::chars(string $str [, string $encoding ])

Returns an array consisting of the characters in the string.

```php
S::create('Fòô Bàř', 'UTF-8')->chars();
S::chars('Fòô Bàř', 'UTF-8');  // array(F', 'ò', 'ô', ' ', 'B', 'à', 'ř')
```

#### collapseWhitespace

$stringy->collapseWhitespace()

S::collapseWhitespace(string $str [, string $encoding ])

Trims the string and replaces consecutive whitespace characters with a
single space. This includes tabs and newline characters, as well as
multibyte whitespace such as the thin space and ideographic space.

```php
S::create('   Ο     συγγραφέας  ')->collapseWhitespace();
S::collapseWhitespace('   Ο     συγγραφέας  ');  // 'Ο συγγραφέας'
```

#### contains

$stringy->contains(string $needle [, boolean $caseSensitive = true ])

S::contains(string $haystack, string $needle [, boolean $caseSensitive = true [, string $encoding ]])

Returns true if the string contains $needle, false otherwise. By default,
the comparison is case-sensitive, but can be made insensitive
by setting $caseSensitive to false.

```php
S::create('Ο συγγραφέας είπε', 'UTF-8')->contains('συγγραφέας');
S::contains('Ο συγγραφέας είπε', 'συγγραφέας', 'UTF-8');  // true
```

#### containsAll

$stringy->containsAll(array $needles [, boolean $caseSensitive = true ])

S::containsAll(string $haystack, array $needles [, boolean $caseSensitive = true [, string $encoding ]])

Returns true if the string contains all $needles, false otherwise. By
default the comparison is case-sensitive, but can be made insensitive by
setting $caseSensitive to false.

```php
S::create('Str contains foo and bar')->containsAll(array('foo', 'bar'));
S::containsAll('Str contains foo and bar', array('foo', 'bar'));  // true
```

#### containsAny

$stringy->containsAny(array $needles [, boolean $caseSensitive = true ])

S::containsAny(string $haystack, array $needles [, boolean $caseSensitive = true [, string $encoding ]])

Returns true if the string contains any $needles, false otherwise. By
default the comparison is case-sensitive, but can be made insensitive by
setting $caseSensitive to false.

```php
S::create('Str contains foo')->containsAny(array('foo', 'bar'));
S::containsAny('Str contains foo', array('foo', 'bar'));  // true
```

#### countSubstr

$stringy->countSubstr(string $substring [, boolean $caseSensitive = true ])

S::countSubstr(string $str, string $substring [, boolean $caseSensitive = true [, string $encoding ]])

Returns the number of occurrences of $substring in the given string.
By default, the comparison is case-sensitive, but can be made insensitive
by setting $caseSensitive to false.

```php
S::create('Ο συγγραφέας είπε', 'UTF-8')->countSubstr('α');
S::countSubstr('Ο συγγραφέας είπε', 'α', 'UTF-8');  // 2
```

#### create

S::create(mixed $str [, $encoding ])

Creates a Stringy object and assigns both str and encoding properties
the supplied values. $str is cast to a string prior to assignment, and if
$encoding is not specified, it defaults to mb_internal_encoding(). It
then returns the initialized object. Throws an InvalidArgumentException
if the first argument is an array or object without a __toString method.

```php
$stringy = S::create('fòô bàř', 'UTF-8');  // 'fòô bàř'
```

#### dasherize

$stringy->dasherize();

S::dasherize(string $str [, string $encoding ])

Returns a lowercase and trimmed string separated by dashes. Dashes are
inserted before uppercase characters (with the exception of the first
character of the string), and in place of spaces as well as underscores.

```php
S::create('TestDCase')->dasherize();
S::dasherize('TestDCase');  // 'test-d-case'
```

#### delimit

$stringy->delimit($delimiter);

S::delimit(string $str [, string $delimiter, string $encoding ])

Returns a lowercase and trimmed string separated by the given delimiter.
Delimiters are inserted before uppercase characters (with the exception
of the first character of the string), and in place of spaces, dashes,
and underscores. Alpha delimiters are not converted to lowercase.

```php
S::create('TestDCase')->delimit('>>');
S::delimit('TestCase', '>>');  // 'test>>case'
```

#### endsWith

$stringy->endsWith(string $substring [, boolean $caseSensitive = true ])

S::endsWith(string $str, string $substring [, boolean $caseSensitive = true [, string $encoding ]])

Returns true if the string ends with $substring, false otherwise. By
default, the comparison is case-sensitive, but can be made insensitive by
setting $caseSensitive to false.

```php
S::create('FÒÔ bàřs', 'UTF-8')->endsWith('àřs', true);
S::endsWith('FÒÔ bàřs', 'àřs', true, 'UTF-8');  // true
```

#### ensureLeft

$stringy->ensureLeft(string $substring)

S::ensureLeft(string $substring [, string $encoding ])

Ensures that the string begins with $substring. If it doesn't, it's prepended.

```php
S::create('foobar')->ensureLeft('http://');
S::ensureLeft('foobar', 'http://');  // 'http://foobar'
```

#### ensureRight

$stringy->ensureRight(string $substring)

S::ensureRight(string $substring [, string $encoding ])

Ensures that the string begins with $substring. If it doesn't, it's appended.

```php
S::create('foobar')->ensureRight('.com');
S::ensureRight('foobar', '.com');  // 'foobar.com'
```

#### first

$stringy->first(int $n)

S::first(int $n [, string $encoding ])

Returns the first $n characters of the string.

```php
S::create('fòô bàř', 'UTF-8')->first(3);
S::first('fòô bàř', 3, 'UTF-8');  // 'fòô'
```

#### getEncoding

$stringy->getEncoding()

Returns the encoding used by the Stringy object.

```php
S::create('fòô bàř', 'UTF-8')->getEncoding();  // 'UTF-8'
```

#### hasLowerCase

$stringy->hasLowerCase()

S::hasLowerCase(string $str [, string $encoding ])

Returns true if the string contains a lower case char, false otherwise.

```php
S::create('fòô bàř', 'UTF-8')->hasLowerCase();
S::hasLowerCase('fòô bàř', 'UTF-8');  // true
```

#### hasUpperCase

$stringy->hasUpperCase()

S::hasUpperCase(string $str [, string $encoding ])

Returns true if the string contains an upper case char, false otherwise.

```php
S::create('fòô bàř', 'UTF-8')->hasUpperCase();
S::hasUpperCase('fòô bàř', 'UTF-8');  // false
```

#### htmlDecode

$stringy->htmlDecode()

S::htmlDecode(string $str [, int $flags, string $encoding ])

Convert all HTML entities to their applicable characters.

```php
S::create('&amp;')->htmlDecode();
S::htmlDecode('&amp;');  // '&'
```

#### htmlEncode

$stringy->htmlEncode()

S::htmlEncode(string $str [, int $flags, string $encoding ])

Convert all applicable characters to HTML entities.

```php
S::create('&')->htmlEncode();
S::htmlEncode('&');  // '&amp;'
```

#### humanize

$stringy->humanize()

S::humanize(string $str [, string $encoding ])

Capitalizes the first word of the string, replaces underscores with
spaces, and strips '_id'.

```php
S::create('author_id')->humanize();
S::humanize('author_id');  // 'Author'
```

#### indexOf

$stringy->indexOf(string $needle [, $offset = 0 ]);

S::indexOf(string $haystack , string $needle [, $offset = 0 [, $encoding = null ]])

Returns the index of the first occurrence of $needle in the string,
and false if not found. Accepts an optional offset from which to begin
the search.

```php
S::create('string', 'UTF-8')->indexOf('ing');
S::indexOf('string', 'ing');  // 3
```

#### indexOfLast

$stringy->indexOfLast(string $needle [, $offset = 0 ]);

S::indexOfLast(string $haystack , string $needle [, $offset = 0 [, $encoding = null ]])

Returns the index of the last occurrence of $needle in the string,
and false if not found. Accepts an optional offset from which to begin
the search.

```php
S::create('string', 'UTF-8')->indexOfLast('ing');
S::indexOfLast('string string', 'ing');  // 10
```

#### insert

$stringy->insert(int $index, string $substring)

S::insert(string $str, int $index, string $substring [, string $encoding ])

Inserts $substring into the string at the $index provided.

```php
S::create('fòô bà', 'UTF-8')->insert('ř', 6);
S::insert('fòô bà', 'ř', 6, 'UTF-8');  // 'fòô bàř'
```

#### isAlpha

$stringy->isAlpha()

S::isAlpha(string $str [, string $encoding ])

Returns true if the string contains only alphabetic chars, false otherwise.

```php
S::create('丹尼爾', 'UTF-8')->isAlpha();
S::isAlpha('丹尼爾', 'UTF-8');  // true
```

#### isAlphanumeric

$stringy->isAlphanumeric()

S::isAlphanumeric(string $str [, string $encoding ])

Returns true if the string contains only alphabetic and numeric chars, false
otherwise.

```php
S::create('دانيال1', 'UTF-8')->isAlphanumeric();
S::isAlphanumeric('دانيال1', 'UTF-8');  // true
```

#### isBlank

$stringy->isBlank()

S::isBlank(string $str [, string $encoding ])

Returns true if the string contains only whitespace chars, false otherwise.

```php
S::create("\n\t  \v\f")->isBlank();
S::isBlank("\n\t  \v\f");  // true
```

#### isHexadecimal

$stringy->isHexadecimal()

S::isHexadecimal(string $str [, string $encoding ])

Returns true if the string contains only hexadecimal chars, false otherwise.

```php
S::create('A102F')->isHexadecimal();
S::isHexadecimal('A102F');  // true
```

#### isJson

$stringy->isJson()

S::isJson(string $str [, string $encoding ])

Returns true if the string is JSON, false otherwise.

```php
S::create('{"foo":"bar"}')->isJson();
S::isJson('{"foo":"bar"}');  // true
```

#### isLowerCase

$stringy->isLowerCase()

S::isLowerCase(string $str [, string $encoding ])

Returns true if the string contains only lower case chars, false otherwise.

```php
S::create('fòô bàř', 'UTF-8')->isLowerCase();
S::isLowerCase('fòô bàř', 'UTF-8');  // true
```

#### isSerialized

$stringy->isSerialized()

S::isSerialized(string $str [, string $encoding ])

Returns true if the string is serialized, false otherwise.

```php
S::create('a:1:{s:3:"foo";s:3:"bar";}', 'UTF-8')->isSerialized();
S::isSerialized('a:1:{s:3:"foo";s:3:"bar";}', 'UTF-8');  // true
```

#### isUpperCase

$stringy->isUpperCase()

S::isUpperCase(string $str [, string $encoding ])

Returns true if the string contains only upper case chars, false otherwise.

```php
S::create('FÒÔBÀŘ', 'UTF-8')->isUpperCase();
S::isUpperCase('FÒÔBÀŘ', 'UTF-8');  // true
```

#### last

$stringy->last(int $n)

S::last(int $n [, string $encoding ])

Returns the last $n characters of the string.

```php
S::create('fòô bàř', 'UTF-8')->last(3);
S::last('fòô bàř', 3, 'UTF-8');  // 'bàř'
```

#### length

$stringy->length()

S::length(string $str [, string $encoding ])

Returns the length of the string. An alias for PHP's mb_strlen() function.

```php
S::create('fòô bàř', 'UTF-8')->length();
S::length('fòô bàř', 'UTF-8');  // 7
```

#### longestCommonPrefix

$stringy->longestCommonPrefix(string $otherStr)

S::longestCommonPrefix(string $str, string $otherStr [, $encoding ])

Returns the longest common prefix between the string and $otherStr.

```php
S::create('fòô bar', 'UTF-8')->longestCommonPrefix('fòr bar');
S::longestCommonPrefix('fòô bar', 'fòr bar', 'UTF-8');  // 'fò'
```

#### longestCommonSuffix

$stringy->longestCommonSuffix(string $otherStr)

S::longestCommonSuffix(string $str, string $otherStr [, $encoding ])

Returns the longest common suffix between the string and $otherStr.

```php
S::create('fòô bàř', 'UTF-8')->longestCommonSuffix('fòr bàř');
S::longestCommonSuffix('fòô bàř', 'fòr bàř', 'UTF-8');  // ' bàř'
```

#### longestCommonSubstring

$stringy->longestCommonSubstring(string $otherStr)

S::longestCommonSubstring(string $str, string $otherStr [, $encoding ])

Returns the longest common substring between the string and $otherStr. In the
case of ties, it returns that which occurs first.

```php
S::create('foo bar')->longestCommonSubstring('boo far');
S::longestCommonSubstring('foo bar', 'boo far');  // 'oo '
```

#### lowerCaseFirst

$stringy->lowerCaseFirst();

S::lowerCaseFirst(string $str [, string $encoding ])

Converts the first character of the supplied string to lower case.

```php
S::create('Σ test', 'UTF-8')->lowerCaseFirst();
S::lowerCaseFirst('Σ test', 'UTF-8');  // 'σ test'
```

#### pad

$stringy->pad(int $length [, string $padStr = ' ' [, string $padType = 'right' ]])

S::pad(string $str , int $length [, string $padStr = ' ' [, string $padType = 'right' [, string $encoding ]]])

Pads the string to a given length with $padStr. If length is less than
or equal to the length of the string, no padding takes places. The default
string used for padding is a space, and the default type (one of 'left',
'right', 'both') is 'right'. Throws an InvalidArgumentException if
$padType isn't one of those 3 values.

```php
S::create('fòô bàř', 'UTF-8')->pad( 10, '¬ø', 'left');
S::pad('fòô bàř', 10, '¬ø', 'left', 'UTF-8');  // '¬ø¬fòô bàř'
```

#### padBoth

$stringy->padBoth(int $length [, string $padStr = ' ' ])

S::padBoth(string $str , int $length [, string $padStr = ' ' [, string $encoding ]])

Returns a new string of a given length such that both sides of the string
string are padded. Alias for pad() with a $padType of 'both'.

```php
S::create('foo bar')->padBoth(9, ' ');
S::padBoth('foo bar', 9, ' ');  // ' foo bar '
```

#### padLeft

$stringy->padLeft(int $length [, string $padStr = ' ' ])

S::padLeft(string $str , int $length [, string $padStr = ' ' [, string $encoding ]])

Returns a new string of a given length such that the beginning of the
string is padded. Alias for pad() with a $padType of 'left'.

```php
S::create($str, $encoding)->padLeft($length, $padStr);
S::padLeft('foo bar', 9, ' ');  // '  foo bar'
```

#### padRight

$stringy->padRight(int $length [, string $padStr = ' ' ])

S::padRight(string $str , int $length [, string $padStr = ' ' [, string $encoding ]])

Returns a new string of a given length such that the end of the string is
padded. Alias for pad() with a $padType of 'right'.

```php
S::create('foo bar')->padRight(10, '_*');
S::padRight('foo bar', 10, '_*');  // 'foo bar_*_'
```

#### regexReplace

$stringy->regexReplace(string $pattern, string $replacement [, string $options = 'msr'])

S::regexReplace(string $str, string $pattern, string $replacement [, string $options = 'msr' [, string $encoding ]])

Replaces all occurrences of $pattern in $str by $replacement. An alias
for mb_ereg_replace(). Note that the 'i' option with multibyte patterns
in mb_ereg_replace() requires PHP 5.4+. This is due to a lack of support
in the bundled version of Oniguruma in PHP 5.3.

```php
S::create('fòô ', 'UTF-8')->regexReplace('f[òô]+\s', 'bàř', 'msr');
S::regexReplace('fòô ', 'f[òô]+\s', 'bàř', 'msr', 'UTF-8');  // 'bàř'
```

#### removeLeft

$stringy->removeLeft(string $substring)

S::removeLeft(string $str, string $substring [, string $encoding ])

Returns a new string with the prefix $substring removed, if present.

```php
S::create('fòô bàř', 'UTF-8')->removeLeft('fòô ');
S::removeLeft('fòô bàř', 'fòô ', 'UTF-8');  // 'bàř'
```

#### removeRight

$stringy->removeRight(string $substring)

S::removeRight(string $str, string $substring [, string $encoding ])

Returns a new string with the suffix $substring removed, if present.

```php
S::create('fòô bàř', 'UTF-8')->removeRight(' bàř');
S::removeRight('fòô bàř', ' bàř', 'UTF-8');  // 'fòô'
```

#### replace

$stringy->replace(string $search, string $replacement)

S::replace(string $str, string $search, string $replacement [, string $encoding ])

Replaces all occurrences of $search in $str by $replacement.

```php
S::create('fòô bàř fòô bàř', 'UTF-8')->replace('fòô ', '');
S::replace('fòô bàř fòô bàř', 'fòô ', '', 'UTF-8');  // 'bàř bàř'
```

#### reverse

$stringy->reverse()

S::reverse(string $str [, string $encoding ])

Returns a reversed string. A multibyte version of strrev().

```php
S::create('fòô bàř', 'UTF-8')->reverse();
S::reverse('fòô bàř', 'UTF-8');  // 'řàb ôòf'
```

#### safeTruncate

$stringy->safeTruncate(int $length [, string $substring = '' ])

S::safeTruncate(string $str, int $length [, string $substring = '' [, string $encoding ]])

Truncates the string to a given length, while ensuring that it does not
split words. If $substring is provided, and truncating occurs, the
string is further truncated so that the substring may be appended without
exceeding the desired length.

```php
S::create('What are your plans today?')->safeTruncate(22, '...');
S::safeTruncate('What are your plans today?', 22, '...');  // 'What are your plans...'
```

#### shuffle

$stringy->shuffle()

S::shuffle(string $str [, string $encoding ])

A multibyte str_shuffle() function. It returns a string with its characters in
random order.

```php
S::create('fòô bàř', 'UTF-8')->shuffle();
S::shuffle('fòô bàř', 'UTF-8');  // 'àôřb òf'
```

#### slugify

$stringy->slugify([ string $replacement = '-' ])

S::slugify(string $str [, string $replacement = '-' ])

Converts the string into an URL slug. This includes replacing non-ASCII
characters with their closest ASCII equivalents, removing remaining
non-ASCII and non-alphanumeric characters, and replacing whitespace with
$replacement. The replacement defaults to a single dash, and the string
is also converted to lowercase.

```php
S::create('Using strings like fòô bàř')->slugify();
S::slugify('Using strings like fòô bàř');  // 'using-strings-like-foo-bar'
```

#### startsWith

$stringy->startsWith(string $substring [, boolean $caseSensitive = true ])

S::startsWith(string $str, string $substring [, boolean $caseSensitive = true [, string $encoding ]])

Returns true if the string begins with $substring, false otherwise.
By default, the comparison is case-sensitive, but can be made insensitive
by setting $caseSensitive to false.

```php
S::create('FÒÔ bàřs', 'UTF-8')->startsWith('fòô bàř', false);
S::startsWith('FÒÔ bàřs', 'fòô bàř', false, 'UTF-8');  // true
```

#### substr

$stringy->substr(int $start [, int $length ])

S::substr(string $str, int $start [, int $length [, string $encoding ]])

Returns the substring beginning at $start with the specified $length.
It differs from the mb_substr() function in that providing a $length of
null will return the rest of the string, rather than an empty string.

```php
S::create('fòô bàř', 'UTF-8')->substr(2, 3);
S::substr('fòô bàř', 2, 3, 'UTF-8');  // 'ô b'
```

#### surround

$stringy->surround(string $substring)

S::surround(string $str, string $substring)

Surrounds a string with the given substring.

```php
S::create(' ͜ ')->surround('ʘ');
S::surround(' ͜ ', 'ʘ');  // 'ʘ ͜ ʘ'
```

#### swapCase

$stringy->swapCase();

S::swapCase(string $str [, string $encoding ])

Returns a case swapped version of the string.

```php
S::create('Ντανιλ', 'UTF-8')->swapCase();
S::swapCase('Ντανιλ', 'UTF-8');  // 'νΤΑΝΙΛ'
```

#### tidy

$stringy->tidy()

S::tidy(string $str)

Returns a string with smart quotes, ellipsis characters, and dashes from
Windows-1252 (commonly used in Word documents) replaced by their ASCII equivalents.

```php
S::create('“I see…”')->tidy();
S::tidy('“I see…”');  // '"I see..."'
```

#### titleize

$stringy->titleize([ string $encoding ])

S::titleize(string $str [, array $ignore [, string $encoding ]])

Returns a trimmed string with the first letter of each word capitalized.
Ignores the case of other letters, preserving any acronyms. Also accepts
an array, $ignore, allowing you to list words not to be capitalized.

```php
$ignore = array('at', 'by', 'for', 'in', 'of', 'on', 'out', 'to', 'the');
S::create('i like to watch DVDs at home', 'UTF-8')->titleize($ignore);
S::titleize('i like to watch DVDs at home', $ignore, 'UTF-8');
// 'I Like to Watch DVDs at Home'
```

#### toAscii

$stringy->toAscii()

S::toAscii(string $str [, boolean $removeUnsupported = true])

Returns an ASCII version of the string. A set of non-ASCII characters are
replaced with their closest ASCII counterparts, and the rest are removed
unless instructed otherwise.

```php
S::create('fòô bàř')->toAscii();
S::toAscii('fòô bàř');  // 'foo bar'
```

#### toLowerCase

$stringy->toLowerCase()

S::toLowerCase(string $str [, string $encoding ])

Converts all characters in the string to lowercase. An alias for PHP's
mb_strtolower().

```php
S::create('FÒÔ BÀŘ', 'UTF-8')->toLowerCase();
S::toLowerCase('FÒÔ BÀŘ', 'UTF-8');  // 'fòô bàř'
```

#### toSpaces

$stringy->toSpaces([ tabLength = 4 ])

S::toSpaces(string $str [, int $tabLength = 4 ])

Converts each tab in the string to some number of spaces, as defined by
$tabLength. By default, each tab is converted to 4 consecutive spaces.

```php
S::create(' String speech = "Hi"')->toSpaces();
S::toSpaces('   String speech = "Hi"');  // '    String speech = "Hi"'
```

#### toTabs

$stringy->toTabs([ tabLength = 4 ])

S::toTabs(string $str [, int $tabLength = 4 ])

Converts each occurrence of some consecutive number of spaces, as defined
by $tabLength, to a tab. By default, each 4 consecutive spaces are
converted to a tab.

```php
S::create('    fòô    bàř')->toTabs();
S::toTabs('    fòô    bàř');  // '   fòô bàř'
```

#### toTitleCase

$stringy->toTitleCase()

S::toTitleCase(string $str [, string $encoding ])

Converts the first character of each word in the string to uppercase.

```php
S::create('fòô bàř', 'UTF-8')->toTitleCase();
S::toTitleCase('fòô bàř', 'UTF-8');  // 'Fòô Bàř'
```

#### toUpperCase

$stringy->toUpperCase()

S::toUpperCase(string $str [, string $encoding ])

Converts all characters in the string to uppercase. An alias for PHP's
mb_strtoupper().

```php
S::create('fòô bàř', 'UTF-8')->toUpperCase();
S::toUpperCase('fòô bàř', 'UTF-8');  // 'FÒÔ BÀŘ'
```

#### trim

$stringy->trim([, string $chars])

S::trim(string $str [, string $chars [, string $encoding ]])

Returns a string with whitespace removed from the start and end of the
string. Supports the removal of unicode whitespace. Accepts an optional
string of characters to strip instead of the defaults.

```php
S::create('  fòô bàř  ', 'UTF-8')->trim();
S::trim('  fòô bàř  ');  // 'fòô bàř'
```

#### trimLeft

$stringy->trimLeft([, string $chars])

S::trimLeft(string $str [, string $chars [, string $encoding ]])

Returns a string with whitespace removed from the start of the string.
Supports the removal of unicode whitespace. Accepts an optional
string of characters to strip instead of the defaults.

```php
S::create('  fòô bàř  ', 'UTF-8')->trimLeft();
S::trimLeft('  fòô bàř  ');  // 'fòô bàř  '
```

#### trimRight

$stringy->trimRight([, string $chars])

S::trimRight(string $str [, string $chars [, string $encoding ]])

Returns a string with whitespace removed from the end of the string.
Supports the removal of unicode whitespace. Accepts an optional
string of characters to strip instead of the defaults.

```php
S::create('  fòô bàř  ', 'UTF-8')->trimRight();
S::trimRight('  fòô bàř  ');  // '  fòô bàř'
```

#### truncate

$stringy->truncate(int $length [, string $substring = '' ])

S::truncate(string $str, int $length [, string $substring = '' [, string $encoding ]])

Truncates the string to a given length. If $substring is provided, and
truncating occurs, the string is further truncated so that the substring
may be appended without exceeding the desired length.

```php
S::create('What are your plans today?')->truncate(19, '...');
S::truncate('What are your plans today?', 19, '...');  // 'What are your pl...'
```

#### underscored

$stringy->underscored();

S::underscored(string $str [, string $encoding ])

Returns a lowercase and trimmed string separated by underscores.
Underscores are inserted before uppercase characters (with the exception
of the first character of the string), and in place of spaces as well as dashes.

```php
S::create('TestUCase')->underscored();
S::underscored('TestUCase');  // 'test_u_case'
```

#### upperCamelize

$stringy->upperCamelize();

S::upperCamelize(string $str [, string $encoding ])

Returns an UpperCamelCase version of the supplied string. It trims
surrounding spaces, capitalizes letters following digits, spaces, dashes
and underscores, and removes spaces, dashes, underscores.

```php
S::create('Upper Camel-Case')->upperCamelize();
S::upperCamelize('Upper Camel-Case');  // 'UpperCamelCase'
```

#### upperCaseFirst

$stringy->upperCaseFirst();

S::upperCaseFirst(string $str [, string $encoding ])

Converts the first character of the supplied string to upper case.

```php
S::create('σ test', 'UTF-8')->upperCaseFirst();
S::upperCaseFirst('σ test', 'UTF-8');  // 'Σ test'
```

## Links

The following is a list of libraries that extend Stringy:

 * [SliceableStringy](https://github.com/danielstjules/SliceableStringy):
Python-like string slices in PHP
 * [SubStringy](https://github.com/TCB13/SubStringy):
Advanced substring methods

## Tests

From the project directory, tests can be ran using `phpunit`

## License

Released under the MIT License - see `LICENSE.txt` for details.
