<?php

$header = <<<'EOF'
The RandomLib library for securely generating random numbers and strings in PHP
 
@author     Anthony Ferrara <ircmaxell@ircmaxell.com>
@copyright  2011 The Authors
@license    http://www.opensource.org/licenses/mit-license.html  MIT License
@version    Build @@version@@
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'align_double_arrow',
        'array_element_no_space_before_comma',
        'array_element_white_space_after_comma',
        'declare_equal_normalize',
        'extra_empty_lines',
        'header_comment',
        'list_commas',
        'multiline_array_trailing_comma',
        'new_with_braces',
        'no_blank_lines_before_namespace',
        'no_empty_comment',
        'no_empty_lines_after_phpdocs',
        'no_empty_phpdoc',
        'no_empty_statement',
        'object_operator',
        'ordered_use',
        'php_unit_dedicate_assert',
        'phpdoc_indent',
        'phpdoc_order',
        'phpdoc_params',
        'phpdoc_scalar',
        'phpdoc_separation',
        'remove_leading_slash_use',
        'remove_lines_between_uses',
        'return',
        'self_accessor',
        'short_bool_cast',
        'short_scalar_cast',
        'single_blank_line_before_namespace',
        'spaces_before_semicolon',
        'ternary_spaces',
        'trim_array_spaces',
        'unneeded_control_parentheses',
        'unused_use',
        'whitespacey_lines',
    ])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__ . "/lib")
            ->in(__DIR__ . "/test")
    )
;