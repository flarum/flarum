<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search;

interface GambitInterface
{
    /**
     * Apply conditions to the searcher for a bit of the search string.
     *
     * @param AbstractSearch $search
     * @param string $bit The piece of the search string.
     * @return bool Whether or not the gambit was active for this bit.
     */
    public function apply(AbstractSearch $search, $bit);
}
