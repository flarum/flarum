<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Database\Migration;

return Migration::addSettings([
    'flarum-tags.max_primary_tags' => '1',
    'flarum-tags.min_primary_tags' => '1',
    'flarum-tags.max_secondary_tags' => '3',
    'flarum-tags.min_secondary_tags' => '0',
]);
