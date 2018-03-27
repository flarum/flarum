<?php

$header = <<<EOF
This file is part of JSON-API.

(c) Toby Zerner <toby.zerner@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__);

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'short_array_syntax',
        'header_comment',
        '-psr0'
    ])
    ->finder($finder);
