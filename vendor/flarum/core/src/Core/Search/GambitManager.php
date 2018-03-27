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

use Illuminate\Contracts\Container\Container;
use LogicException;

/**
 * @todo This whole gambits thing needs way better documentation.
 */
class GambitManager
{
    /**
     * @var array
     */
    protected $gambits = [];

    /**
     * @var string
     */
    protected $fulltextGambit;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a gambit.
     *
     * @param string $gambit
     */
    public function add($gambit)
    {
        $this->gambits[] = $gambit;
    }

    /**
     * Apply gambits to a search, given a search query.
     *
     * @param AbstractSearch $search
     * @param string $query
     */
    public function apply(AbstractSearch $search, $query)
    {
        $query = $this->applyGambits($search, $query);

        if ($query) {
            $this->applyFulltext($search, $query);
        }
    }

    /**
     * Set the gambit to handle fulltext searching.
     *
     * @param string $gambit
     */
    public function setFulltextGambit($gambit)
    {
        $this->fulltextGambit = $gambit;
    }

    /**
     * Explode a search query into an array of bits.
     *
     * @param string $query
     * @return array
     */
    protected function explode($query)
    {
        return str_getcsv($query, ' ');
    }

    /**
     * @param AbstractSearch $search
     * @param string $query
     * @return string
     */
    protected function applyGambits(AbstractSearch $search, $query)
    {
        $bits = $this->explode($query);

        if (! $bits) {
            return '';
        }

        $gambits = array_map([$this->container, 'make'], $this->gambits);

        foreach ($bits as $k => $bit) {
            foreach ($gambits as $gambit) {
                if (! $gambit instanceof GambitInterface) {
                    throw new LogicException(
                        'Gambit '.get_class($gambit).' does not implement '.GambitInterface::class
                    );
                }

                if ($gambit->apply($search, $bit)) {
                    $search->addActiveGambit($gambit);
                    unset($bits[$k]);
                    break;
                }
            }
        }

        return implode(' ', $bits);
    }

    /**
     * @param AbstractSearch $search
     * @param string $query
     */
    protected function applyFulltext(AbstractSearch $search, $query)
    {
        if (! $this->fulltextGambit) {
            return;
        }

        $gambit = $this->container->make($this->fulltextGambit);

        $search->addActiveGambit($gambit);
        $gambit->apply($search, $query);
    }
}
