#!/usr/bin/env php
<?php

function stringtobytes($amount) {
    // convert "256M" etc to bytes
    switch(strtolower(substr($amount, -1))) {
        case 'g':
            $amount = (int)$amount * 1024;
        case 'm':
            $amount = (int)$amount * 1024;
        case 'k':
            $amount = (int)$amount * 1024;
        default:
            $amount = (int)$amount;
    }
    return $amount;
}

// if given, parse FPM configs as well to figure out the memory limit
// (it may have been set in the FPM config or config include)
function get_fpm_memory_limit($fpmconf, $startsection = "global") {
    if(!is_readable($fpmconf)) {
        return array();
    }
    $fpm = parse_ini_string("[$startsection]\n".file_get_contents($fpmconf), true); // prepend section from parent so stuff is parsed correctly in includes that lack a leading section marker
    
    $retval = array();
    
    foreach($fpm as $section => $directives) {
        foreach($directives as $key => $value) {
            if($section == "www" && $key == "php_admin_value" && isset($value['memory_limit'])) {
                $retval['php_admin_value'] = $value['memory_limit'];
            } elseif($section == "www" && $key == "php_value" && isset($value['memory_limit']) && !isset($retval['php_value'])) {
                // an existing value takes precedence
                // we can only emulate that for includes; within the same file, the INI parser overwrites earlier values :(
                $retval['php_value'] = $value['memory_limit'];
            } elseif($key == "include") {
                // values from the include don't overwrite existing values
                $retval = array_merge(get_fpm_memory_limit($value, $section), $retval);
            }
            
            if(isset($retval['php_admin_value'])) {
                // done for good as nothing can change this anymore, bubble upwards
                return $retval;
            }
        }
    }
    
    return $retval;
}

$opts = getopt("y:t:");

$limits = get_fpm_memory_limit(isset($opts["y"]) ? $opts["y"] : null);
    
if(
    !$limits /* .user.ini memory limit is ignored if one is set via FPM */ &&
    isset($opts['t']) &&
    is_readable($opts['t'].'/.user.ini')
) {
    // we only read the topmost .user.ini inside document root
    $userini = parse_ini_file($opts['t'].'/.user.ini');
    if(isset($userini['memory_limit'])) {
        $limits['php_value'] = $userini['memory_limit'];
    }
}

if(isset($limits['php_admin_value'])) {
    ini_set('memory_limit', $limits['php_admin_value']);
} elseif(isset($limits['php_value'])) {
    ini_set('memory_limit', $limits['php_value']);
}

$limit = ini_get('memory_limit');
$ram = stringtobytes($argv[$argc-1]); // last arg is the available memory

// assume 64 MB base overhead for web server and FPM, and 1 MB overhead for each worker
// echo floor(($ram-stringtobytes('64M'))/(stringtobytes($limit)+stringtobytes('1M'))) . " " . $limit;
echo floor($ram / (stringtobytes($limit)?:-1)) . " " . $limit;
