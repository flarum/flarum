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

abstract class AbstractRegexGambit implements GambitInterface
{
    /**
     * The regex pattern to match the bit against.
     *
     * @var string
     */
    protected $pattern;

    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
    {
        if ($matches = $this->match($bit)) {
            list($negate) = array_splice($matches, 1, 1);

            $this->conditions($search, $matches, (bool) $negate);
        }

        return (bool) $matches;
    }

    /**
     * Match the bit against this gambit.
     *
     * @param string $bit
     * @return array
     */
    protected function match($bit)
    {
        if (preg_match('/^(-?)'.$this->pattern.'$/i', $bit, $matches)) {
            return $matches;
        }
    }

    /**
     * Apply conditions to the search, given that the gambit was matched.
     *
     * @param AbstractSearch $search The search object.
     * @param array $matches An array of matches from the search bit.
     * @param bool $negate Whether or not the bit was negated, and thus whether
     *     or not the conditions should be negated.
     * @return mixed
     */
    abstract protected function conditions(AbstractSearch $search, array $matches, $negate);
}
