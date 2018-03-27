<?php

namespace Dflydev\FigCookies;

class StringUtil
{
    public static function splitOnAttributeDelimiter($string)
    {
        return array_filter(preg_split('@\s*[;]\s*@', $string));
    }

    public static function splitCookiePair($string)
    {
        $pairParts = explode('=', $string, 2);

        if (count($pairParts) === 1) {
            $pairParts[1] = '';
        }

        return array_map(function ($part) {
            return urldecode($part);
        }, $pairParts);
    }
}
