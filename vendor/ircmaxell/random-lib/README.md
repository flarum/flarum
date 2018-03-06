RandomLib
=========

[![Build Status](https://travis-ci.org/ircmaxell/RandomLib.svg?branch=master)](https://travis-ci.org/ircmaxell/RandomLib)

A library for generating random numbers and strings of various strengths.

This library is useful in security contexts.

Install
-------

Via Composer

```sh
$ composer require ircmaxell/random-lib
```

Usage
-----

### Factory

A factory is used to get generators of varying strength:

```php
$factory = new RandomLib\Factory;
$generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));
```

A factory can be configured with additional mixers and sources but can be
used out of the box to create both medium and low strength generators.

Convenience methods are provided for creating high, medium, and low
strength generators. Example:

```php
$generator = $factory->getMediumStrengthGenerator();
```

#### $factory->getLowStrengthGenerator()

Convenience method to get a low strength random number generator.

Low Strength should be used anywhere that random strings are needed in a
non-cryptographical setting.  They are not strong enough to be used as
keys or salts.  They are however useful for one-time use tokens.


#### $factory->getMediumStrengthGenerator()

Convenience method to get a medium strength random number generator.

Medium Strength should be used for most needs of a cryptographic nature.
They are strong enough to be used as keys and salts.  However, they do
take some time and resources to generate, so they should not be over-used


#### $factory->getHighStrengthGenerator()

Convenience method to get a high strength random number generator.

High Strength keys should ONLY be used for generating extremely strong
cryptographic keys.  Generating them is very resource intensive and may
take several minutes or more depending on the requested size.

**There are currently no mixers shipped with this package that are
capable of creating a high space generator. This will not work out of
the box!**


### Generator

A generator is used to generate random numbers and strings.

Example:

```php
// Generate a random string that is 32 bytes in length.
$bytes = $generator->generate(32);

// Generate a whole number between 5 and 15.
$randomInt = $generator->generateInt(5, 15);

// Generate a 32 character string that only contains the letters
// 'a', 'b', 'c', 'd', 'e', and 'f'.
$randomString = $generator->generateString(32, 'abcdef');
```

#### $generator->generate($size)

Generate a random byte string of the requested size.


#### $generator->generateInt($min = 0, $max = PHP_INT_MAX)

Generate a random integer with the given range. If range (`$max - $min`)
is zero, `$max` will be used.


#### $generator->generateString($length, $characters = '')

Generate a random string of specified length.

This uses the supplied character list for generating the new result
string. The list of characters should be specified as a string containing
each allowed character.

If no character list is specified, the following list of characters is used:

    0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ+/

**Examples:**

```php
// Give the character list 'abcdef':
print $generator->generateString(32, 'abcdef')."\n";

// One would expect to receive output that only contained those
// characters:
//
// adaeabecfbddcdaeedaedfbbcdccccfe
// adfbfdbfddadbfcbbefebcacbefafffa
// ceeadbcabecbccacdcaabbdccfadbafe
// abadcffabdcacdbcbafcaecabafcdbbf
// dbdbddacdeaceabfaefcbfafebcacdca
```

License
-------

MIT, see LICENSE.


Community
---------

If you have questions or want to help out, join us in the **#php.security**
channel on **irc.freenode.net**.

Security Vulnerabilities
========================

If you have found a security issue, please contact the author directly at [me@ircmaxell.com](mailto:me@ircmaxell.com).
