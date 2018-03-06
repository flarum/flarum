<?php

require __DIR__ . '/../src/StaticStringy.php';

use Stringy\StaticStringy as S;

class StaticStringyTestCase extends CommonTest
{
	/**
     * @dataProvider indexOfProvider()
     */
    public function testIndexOf($expected, $str, $subStr, $offset = 0, $encoding = null)
    {
        $result = S::indexOf($str, $subStr, $offset, $encoding);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider indexOfLastProvider()
     */
    public function testIndexOfLast($expected, $str, $subStr, $offset = 0, $encoding = null)
    {
        $result = S::indexOfLast($str, $subStr, $offset, $encoding);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider charsProvider()
     */
    public function testChars($expected, $str, $encoding = null)
    {
        $result = S::chars($str, $encoding);
        $this->assertInternalType('array', $result);
        foreach ($result as $char) {
            $this->assertInternalType('string', $char);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider upperCaseFirstProvider()
     */
    public function testUpperCaseFirst($expected, $str, $encoding = null)
    {
        $result = S::upperCaseFirst($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider lowerCaseFirstProvider()
     */
    public function testLowerCaseFirst($expected, $str, $encoding = null)
    {
        $result = S::lowerCaseFirst($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider camelizeProvider()
     */
    public function testCamelize($expected, $str, $encoding = null)
    {
        $result = S::camelize($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider upperCamelizeProvider()
     */
    public function testUpperCamelize($expected, $str, $encoding = null)
    {
        $result = S::upperCamelize($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider dasherizeProvider()
     */
    public function testDasherize($expected, $str, $encoding = null)
    {
        $result = S::dasherize($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider underscoredProvider()
     */
    public function testUnderscored($expected, $str, $encoding = null)
    {
        $result = S::underscored($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider swapCaseProvider()
     */
    public function testSwapCase($expected, $str, $encoding = null)
    {
        $result = S::swapCase($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider titleizeProvider()
     */
    public function testTitleize($expected, $str, $ignore = null,
                                 $encoding = null)
    {
        $result = S::titleize($str, $ignore, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider humanizeProvider()
     */
    public function testHumanize($expected, $str, $encoding = null)
    {
        $result = S::humanize($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider tidyProvider()
     */
    public function testTidy($expected, $str)
    {
        $result = S::tidy($str);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider collapseWhitespaceProvider()
     */
    public function testCollapseWhitespace($expected, $str, $encoding = null)
    {
        $result = S::collapseWhitespace($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider toAsciiProvider()
     */
    public function testToAscii($expected, $str, $removeUnsupported = true)
    {
        $result = S::toAscii($str, $removeUnsupported);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider padProvider()
     */
    public function testPad($expected, $str, $length, $padStr = ' ',
                            $padType = 'right', $encoding = null)
    {
        $result = S::pad($str, $length, $padStr, $padType, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPadException()
    {
        $result = S::pad('string', 5, 'foo', 'bar');
    }

    /**
     * @dataProvider padLeftProvider()
     */
    public function testPadLeft($expected, $str, $length, $padStr = ' ',
                                $encoding = null)
    {
        $result = S::padLeft($str, $length, $padStr, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider padRightProvider()
     */
    public function testPadRight($expected, $str, $length, $padStr = ' ',
                                 $encoding = null)
    {
        $result = S::padRight($str, $length, $padStr, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider padBothProvider()
     */
    public function testPadBoth($expected, $str, $length, $padStr = ' ',
                                $encoding = null)
    {
        $result = S::padBoth($str, $length, $padStr, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider startsWithProvider()
     */
    public function testStartsWith($expected, $str, $substring,
                                   $caseSensitive = true, $encoding = null)
    {
        $result = S::startsWith($str, $substring, $caseSensitive, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider endsWithProvider()
     */
    public function testEndsWith($expected, $str, $substring,
                                 $caseSensitive = true, $encoding = null)
    {
        $result = S::endsWith($str, $substring, $caseSensitive, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider toSpacesProvider()
     */
    public function testToSpaces($expected, $str, $tabLength = 4)
    {
        $result = S::toSpaces($str, $tabLength);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider toTabsProvider()
     */
    public function testToTabs($expected, $str, $tabLength = 4)
    {
        $result = S::toTabs($str, $tabLength);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider toLowerCaseProvider()
     */
    public function testToLowerCase($expected, $str, $encoding = null)
    {
        $result = S::toLowerCase($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider toTitleCaseProvider()
     */
    public function testToTitleCase($expected, $str, $encoding = null)
    {
        $result = S::toTitleCase($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider toUpperCaseProvider()
     */
    public function testToUpperCase($expected, $str, $encoding = null)
    {
        $result = S::toUpperCase($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider slugifyProvider()
     */
    public function testSlugify($expected, $str, $replacement = '-')
    {
        $result = S::slugify($str, $replacement);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider containsProvider()
     */
    public function testContains($expected, $haystack, $needle,
                                 $caseSensitive = true, $encoding = null)
    {
        $result = S::contains($haystack, $needle, $caseSensitive, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider containsAnyProvider()
     */
    public function testcontainsAny($expected, $haystack, $needles,
                                    $caseSensitive = true, $encoding = null)
    {
        $result = S::containsAny($haystack, $needles, $caseSensitive, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider containsAllProvider()
     */
    public function testContainsAll($expected, $haystack, $needles,
                                    $caseSensitive = true, $encoding = null)
    {
        $result = S::containsAll($haystack, $needles, $caseSensitive, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider surroundProvider()
     */
    public function testSurround($expected, $str, $substring)
    {
        $result = S::surround($str, $substring);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider insertProvider()
     */
    public function testInsert($expected, $str, $substring, $index,
                               $encoding = null)
    {
        $result = S::insert($str, $substring, $index, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider truncateProvider()
     */
    public function testTruncate($expected, $str, $length, $substring = '',
                                 $encoding = null)
    {
        $result = S::truncate($str, $length, $substring, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider safeTruncateProvider()
     */
    public function testSafeTruncate($expected, $str, $length, $substring = '',
                                     $encoding = null)
    {
        $result = S::safeTruncate($str, $length, $substring, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider reverseProvider()
     */
    public function testReverse($expected, $str, $encoding = null)
    {
        $result = S::reverse($str, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider shuffleProvider()
     */
    public function testShuffle($str, $encoding = null)
    {
        $result = S::shuffle($str, $encoding);
        $encoding = $encoding ?: mb_internal_encoding();

        $this->assertInternalType('string', $result);
        $this->assertEquals(mb_strlen($str, $encoding),
            mb_strlen($result, $encoding));

        // We'll make sure that the chars are present after shuffle
        for ($i = 0; $i < mb_strlen($str, $encoding); $i++) {
            $char = mb_substr($str, $i, 1, $encoding);
            $countBefore = mb_substr_count($str, $char, $encoding);
            $countAfter = mb_substr_count($result, $char, $encoding);
            $this->assertEquals($countBefore, $countAfter);
        }
    }

    /**
     * @dataProvider trimProvider()
     */
    public function testTrim($expected, $str, $chars = null, $encoding = null)
    {
        $result = S::trim($str, $chars, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider trimLeftProvider()
     */
    public function testTrimLeft($expected, $str, $chars = null,
                                 $encoding = null)
    {
        $result = S::trimLeft($str, $chars, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider trimRightProvider()
     */
    public function testTrimRight($expected, $str, $chars = null,
                                  $encoding = null)
    {
        $result = S::trimRight($str, $chars, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider longestCommonPrefixProvider()
     */
    public function testLongestCommonPrefix($expected, $str, $otherStr,
                                            $encoding = null)
    {
        $result = S::longestCommonPrefix($str, $otherStr, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider longestCommonSuffixProvider()
     */
    public function testLongestCommonSuffix($expected, $str, $otherStr,
                                            $encoding = null)
    {
        $result = S::longestCommonSuffix($str, $otherStr, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider longestCommonSubstringProvider()
     */
    public function testLongestCommonSubstring($expected, $str, $otherStr,
                                               $encoding = null)
    {
        $result = S::longestCommonSubstring($str, $otherStr, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider lengthProvider()
     */
    public function testLength($expected, $str, $encoding = null)
    {
        $result = S::length($str, $encoding);
        $this->assertEquals($expected, $result);
        $this->assertInternalType('int', $result);
    }

    /**
     * @dataProvider substrProvider()
     */
    public function testSubstr($expected, $str, $start, $length = null,
                               $encoding = null)
    {
        $result = S::substr($str, $start, $length, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider atProvider()
     */
    public function testAt($expected, $str, $index, $encoding = null)
    {
        $result = S::at($str, $index, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider firstProvider()
     */
    public function testFirst($expected, $str, $n, $encoding = null)
    {
        $result = S::first($str, $n, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider lastProvider()
     */
    public function testLast($expected, $str, $n, $encoding = null)
    {
        $result = S::last($str, $n, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider ensureLeftProvider()
     */
    public function testEnsureLeft($expected, $str, $substring, $encoding = null)
    {
        $result = S::ensureLeft($str, $substring, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider ensureRightProvider()
     */
    public function testEnsureRight($expected, $str, $substring, $encoding = null)
    {
        $result = S::ensureRight($str, $substring, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider removeLeftProvider()
     */
    public function testRemoveLeft($expected, $str, $substring, $encoding = null)
    {
        $result = S::removeLeft($str, $substring, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider removeRightProvider()
     */
    public function testRemoveRight($expected, $str, $substring, $encoding = null)
    {
        $result = S::removeRight($str, $substring, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isAlphaProvider()
     */
    public function testIsAlpha($expected, $str, $encoding = null)
    {
        $result = S::isAlpha($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isAlphanumericProvider()
     */
    public function testIsAlphanumeric($expected, $str, $encoding = null)
    {
        $result = S::isAlphanumeric($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isBlankProvider()
     */
    public function testIsBlank($expected, $str, $encoding = null)
    {
        $result = S::isBlank($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isJsonProvider()
     */
    public function testIsJson($expected, $str, $encoding = null)
    {
        $result = S::isJson($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isLowerCaseProvider()
     */
    public function testIsLowerCase($expected, $str, $encoding = null)
    {
        $result = S::isLowerCase($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider hasLowerCaseProvider()
     */
    public function testHasLowerCase($expected, $str, $encoding = null)
    {
        $result = S::hasLowerCase($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isSerializedProvider()
     */
    public function testIsSerialized($expected, $str, $encoding = null)
    {
        $result = S::isSerialized($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isUpperCaseProvider()
     */
    public function testIsUpperCase($expected, $str, $encoding = null)
    {
        $result = S::isUpperCase($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider hasUpperCaseProvider()
     */
    public function testHasUpperCase($expected, $str, $encoding = null)
    {
        $result = S::hasUpperCase($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider isHexadecimalProvider()
     */
    public function testIsHexadecimal($expected, $str, $encoding = null)
    {
        $result = S::isHexadecimal($str, $encoding);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider countSubstrProvider()
     */
    public function testCountSubstr($expected, $str, $substring,
                                    $caseSensitive = true, $encoding = null)
    {
        $result = S::countSubstr($str, $substring, $caseSensitive, $encoding);
        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider replaceProvider()
     */
    public function testReplace($expected, $str, $search, $replacement,
                                $encoding = null)
    {
        $result = S::replace($str, $search, $replacement, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider regexReplaceProvider()
     */
    public function testRegexReplace($expected, $str, $pattern, $replacement,
                                     $options = 'msr', $encoding = null)
    {
        $result = S::regexReplace($str, $pattern, $replacement, $options, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider htmlEncodeProvider()
     */
    public function testHtmlEncode($expected, $str, $flags = ENT_COMPAT, $encoding = null)
    {
        $result = S::htmlEncode($str, $flags, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider htmlDecodeProvider()
     */
    public function testHtmlDecode($expected, $str, $flags = ENT_COMPAT, $encoding = null)
    {
        $result = S::htmlDecode($str, $flags, $encoding);
        $this->assertInternalType('string', $result);
        $this->assertEquals($expected, $result);
    }
}
