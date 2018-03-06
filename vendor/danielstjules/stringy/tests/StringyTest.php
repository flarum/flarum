<?php

require __DIR__ . '/../src/Stringy.php';

use Stringy\Stringy as S;

class StringyTestCase extends CommonTest
{
    public function testConstruct()
    {
        $stringy = new S('foo bar', 'UTF-8');
        $this->assertStringy($stringy);
        $this->assertEquals('foo bar', (string) $stringy);
        $this->assertEquals('UTF-8', $stringy->getEncoding());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstructWithArray()
    {
        (string) new S(array());
        $this->fail('Expecting exception when the constructor is passed an array');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingToString()
    {
        (string) new S(new stdClass());
        $this->fail('Expecting exception when the constructor is passed an ' .
                    'object without a __toString method');
    }

    /**
     * @dataProvider toStringProvider()
     */
    public function testToString($expected, $str)
    {
        $this->assertEquals($expected, (string) new S($str));
    }

    public function toStringProvider()
    {
        return array(
            array('', null),
            array('', false),
            array('1', true),
            array('-9', -9),
            array('1.18', 1.18),
            array(' string  ', ' string  ')
        );
    }

    public function testCreate()
    {
        $stringy = S::create('foo bar', 'UTF-8');
        $this->assertStringy($stringy);
        $this->assertEquals('foo bar', (string) $stringy);
        $this->assertEquals('UTF-8', $stringy->getEncoding());
    }

    public function testChaining()
    {
        $stringy = S::create("Fòô     Bàř", 'UTF-8');
        $this->assertStringy($stringy);
        $result = $stringy->collapseWhitespace()->swapCase()->upperCaseFirst();
        $this->assertEquals('FÒÔ bÀŘ', $result);
    }

    public function testCount()
    {
        $stringy = S::create('Fòô', 'UTF-8');
        $this->assertEquals(3, $stringy->count());
        $this->assertEquals(3, count($stringy));
    }

    public function testGetIterator()
    {
        $stringy = S::create('Fòô Bàř', 'UTF-8');

        $valResult = array();
        foreach ($stringy as $char) {
            $valResult[] = $char;
        }

        $keyValResult = array();
        foreach ($stringy as $pos => $char) {
            $keyValResult[$pos] = $char;
        }

        $this->assertEquals(array('F', 'ò', 'ô', ' ', 'B', 'à', 'ř'), $valResult);
        $this->assertEquals(array('F', 'ò', 'ô', ' ', 'B', 'à', 'ř'), $keyValResult);
    }

    /**
     * @dataProvider offsetExistsProvider()
     */
    public function testOffsetExists($expected, $offset)
    {
        $stringy = S::create('fòô', 'UTF-8');
        $this->assertEquals($expected, $stringy->offsetExists($offset));
        $this->assertEquals($expected, isset($stringy[$offset]));
    }

    public function offsetExistsProvider()
    {
        return array(
            array(true, 0),
            array(true, 2),
            array(false, 3),
            array(true, -1),
            array(true, -3),
            array(false, -4)
        );
    }

    public function testOffsetGet()
    {
        $stringy = S::create('fòô', 'UTF-8');

        $this->assertEquals('f', $stringy->offsetGet(0));
        $this->assertEquals('ô', $stringy->offsetGet(2));

        $this->assertEquals('ô', $stringy[2]);
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testOffsetGetOutOfBounds()
    {
        $stringy = S::create('fòô', 'UTF-8');
        $test = $stringy[3];
    }

    /**
     * @expectedException \Exception
     */
    public function testOffsetSet()
    {
        $stringy = S::create('fòô', 'UTF-8');
        $stringy[1] = 'invalid';
    }

    /**
     * @expectedException \Exception
     */
    public function testOffsetUnset()
    {
        $stringy = S::create('fòô', 'UTF-8');
        unset($stringy[1]);
    }

    /**
     * @dataProvider indexOfProvider()
     */
    public function testIndexOf($expected, $str, $subStr, $offset = 0, $encoding = null)
    {
        $result = S::create($str, $encoding)->indexOf($subStr, $offset);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider indexOfLastProvider()
     */
    public function testIndexOfLast($expected, $str, $subStr, $offset = 0, $encoding = null)
    {
        $result = S::create($str, $encoding)->indexOfLast($subStr, $offset);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider charsProvider()
     */
    public function testChars($expected, $str, $encoding = null)
    {
        $result = S::create($str, $encoding)->chars();
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
        $result = S::create($str, $encoding)->upperCaseFirst();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider lowerCaseFirstProvider()
     */
    public function testLowerCaseFirst($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->lowerCaseFirst();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider camelizeProvider()
     */
    public function testCamelize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->camelize();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider upperCamelizeProvider()
     */
    public function testUpperCamelize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->upperCamelize();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider dasherizeProvider()
     */
    public function testDasherize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->dasherize();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider underscoredProvider()
     */
    public function testUnderscored($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->underscored();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider delimitProvider()
     */
    public function testDelimit($expected, $str, $delimiter, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->delimit($delimiter);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider swapCaseProvider()
     */
    public function testSwapCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->swapCase();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider titleizeProvider()
     */
    public function testTitleize($expected, $str, $ignore = null,
                                 $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->titleize($ignore);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider humanizeProvider()
     */
    public function testHumanize($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->humanize();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider tidyProvider()
     */
    public function testTidy($expected, $str)
    {
        $stringy = S::create($str);
        $result = $stringy->tidy();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider collapseWhitespaceProvider()
     */
    public function testCollapseWhitespace($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->collapseWhitespace();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider toAsciiProvider()
     */
    public function testToAscii($expected, $str, $removeUnsupported = true)
    {
        $stringy = S::create($str);
        $result = $stringy->toAscii($removeUnsupported);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider padProvider()
     */
    public function testPad($expected, $str, $length, $padStr = ' ',
                            $padType = 'right', $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->pad($length, $padStr, $padType);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPadException()
    {
        $stringy = S::create('foo');
        $result = $stringy->pad(5, 'foo', 'bar');
    }

    /**
     * @dataProvider padLeftProvider()
     */
    public function testPadLeft($expected, $str, $length, $padStr = ' ',
                                $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->padLeft($length, $padStr);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider padRightProvider()
     */
    public function testPadRight($expected, $str, $length, $padStr = ' ',
                                 $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->padRight($length, $padStr);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider padBothProvider()
     */
    public function testPadBoth($expected, $str, $length, $padStr = ' ',
                                $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->padBoth($length, $padStr);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider startsWithProvider()
     */
    public function testStartsWith($expected, $str, $substring,
                                   $caseSensitive = true, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->startsWith($substring, $caseSensitive);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider endsWithProvider()
     */
    public function testEndsWith($expected, $str, $substring,
                                 $caseSensitive = true, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->endsWith($substring, $caseSensitive);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider toSpacesProvider()
     */
    public function testToSpaces($expected, $str, $tabLength = 4)
    {
        $stringy = S::create($str);
        $result = $stringy->toSpaces($tabLength);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider toTabsProvider()
     */
    public function testToTabs($expected, $str, $tabLength = 4)
    {
        $stringy = S::create($str);
        $result = $stringy->toTabs($tabLength);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider toLowerCaseProvider()
     */
    public function testToLowerCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toLowerCase();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider toTitleCaseProvider()
     */
    public function testToTitleCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toTitleCase();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider toUpperCaseProvider()
     */
    public function testToUpperCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->toUpperCase();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider slugifyProvider()
     */
    public function testSlugify($expected, $str, $replacement = '-')
    {
        $stringy = S::create($str);
        $result = $stringy->slugify($replacement);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider containsProvider()
     */
    public function testContains($expected, $haystack, $needle,
                                 $caseSensitive = true, $encoding = null)
    {
        $stringy = S::create($haystack, $encoding);
        $result = $stringy->contains($needle, $caseSensitive);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($haystack, $stringy);
    }

    /**
     * @dataProvider containsAnyProvider()
     */
    public function testcontainsAny($expected, $haystack, $needles,
                                    $caseSensitive = true, $encoding = null)
    {
        $stringy = S::create($haystack, $encoding);
        $result = $stringy->containsAny($needles, $caseSensitive);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($haystack, $stringy);
    }

    /**
     * @dataProvider containsAllProvider()
     */
    public function testContainsAll($expected, $haystack, $needles,
                                    $caseSensitive = true, $encoding = null)
    {
        $stringy = S::create($haystack, $encoding);
        $result = $stringy->containsAll($needles, $caseSensitive);
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($haystack, $stringy);
    }

    /**
     * @dataProvider surroundProvider()
     */
    public function testSurround($expected, $str, $substring)
    {
        $stringy = S::create($str);
        $result = $stringy->surround($substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider insertProvider()
     */
    public function testInsert($expected, $str, $substring, $index,
                               $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->insert($substring, $index);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider truncateProvider()
     */
    public function testTruncate($expected, $str, $length, $substring = '',
                                 $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->truncate($length, $substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider safeTruncateProvider()
     */
    public function testSafeTruncate($expected, $str, $length, $substring = '',
                                     $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->safeTruncate($length, $substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider reverseProvider()
     */
    public function testReverse($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->reverse();
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider shuffleProvider()
     */
    public function testShuffle($str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $encoding = $encoding ?: mb_internal_encoding();
        $result = $stringy->shuffle();

        $this->assertStringy($result);
        $this->assertEquals($str, $stringy);
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
        $stringy = S::create($str, $encoding);
        $result = $stringy->trim($chars);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider trimLeftProvider()
     */
    public function testTrimLeft($expected, $str, $chars = null,
                                 $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->trimLeft($chars);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider trimRightProvider()
     */
    public function testTrimRight($expected, $str, $chars = null,
                                  $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->trimRight($chars);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider longestCommonPrefixProvider()
     */
    public function testLongestCommonPrefix($expected, $str, $otherStr,
                                            $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->longestCommonPrefix($otherStr);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider longestCommonSuffixProvider()
     */
    public function testLongestCommonSuffix($expected, $str, $otherStr,
                                            $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->longestCommonSuffix($otherStr);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider longestCommonSubstringProvider()
     */
    public function testLongestCommonSubstring($expected, $str, $otherStr,
                                               $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->longestCommonSubstring($otherStr);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider lengthProvider()
     */
    public function testLength($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->length();
        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider substrProvider()
     */
    public function testSubstr($expected, $str, $start, $length = null,
                               $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->substr($start, $length);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider atProvider()
     */
    public function testAt($expected, $str, $index, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->at($index);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider firstProvider()
     */
    public function testFirst($expected, $str, $n, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->first($n);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider lastProvider()
     */
    public function testLast($expected, $str, $n, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->last($n);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider ensureLeftProvider()
     */
    public function testEnsureLeft($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->ensureLeft($substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider ensureRightProvider()
     */
    public function testEnsureRight($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->ensureRight($substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider removeLeftProvider()
     */
    public function testRemoveLeft($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->removeLeft($substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider removeRightProvider()
     */
    public function testRemoveRight($expected, $str, $substring, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->removeRight($substring);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isAlphaProvider()
     */
    public function testIsAlpha($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isAlpha();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isAlphanumericProvider()
     */
    public function testIsAlphanumeric($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isAlphanumeric();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isBlankProvider()
     */
    public function testIsBlank($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isBlank();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isJsonProvider()
     */
    public function testIsJson($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isJson();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isLowerCaseProvider()
     */
    public function testIsLowerCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isLowerCase();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider hasLowerCaseProvider()
     */
    public function testHasLowerCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->hasLowerCase();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isSerializedProvider()
     */
    public function testIsSerialized($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isSerialized();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isUpperCaseProvider()
     */
    public function testIsUpperCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isUpperCase();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider hasUpperCaseProvider()
     */
    public function testHasUpperCase($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->hasUpperCase();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider isHexadecimalProvider()
     */
    public function testIsHexadecimal($expected, $str, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->isHexadecimal();
        $this->assertInternalType('boolean', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider countSubstrProvider()
     */
    public function testCountSubstr($expected, $str, $substring,
                                    $caseSensitive = true, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->countSubstr($substring, $caseSensitive);
        $this->assertInternalType('int', $result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider replaceProvider()
     */
    public function testReplace($expected, $str, $search, $replacement,
                                $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->replace($search, $replacement);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider regexReplaceProvider()
     */
    public function testregexReplace($expected, $str, $pattern, $replacement,
                                     $options = 'msr', $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->regexReplace($pattern, $replacement, $options);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider htmlEncodeProvider()
     */
    public function testHtmlEncode($expected, $str, $flags = ENT_COMPAT, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->htmlEncode($flags);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }

    /**
     * @dataProvider htmlDecodeProvider()
     */
    public function testHtmlDecode($expected, $str, $flags = ENT_COMPAT, $encoding = null)
    {
        $stringy = S::create($str, $encoding);
        $result = $stringy->htmlDecode($flags);
        $this->assertStringy($result);
        $this->assertEquals($expected, $result);
        $this->assertEquals($str, $stringy);
    }
}
