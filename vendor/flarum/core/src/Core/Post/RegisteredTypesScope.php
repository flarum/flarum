<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Post;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;

class RegisteredTypesScope implements ScopeInterface
{
    /**
     * The index at which we added a where clause.
     *
     * @var int
     */
    protected $whereIndex;

    /**
     * The index at which we added where bindings.
     *
     * @var int
     */
    protected $bindingIndex;

    /**
     * The number of where bindings we added.
     *
     * @var int
     */
    protected $bindingCount;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $post
     * @return void
     */
    public function apply(Builder $builder, Model $post)
    {
        $query = $builder->getQuery();

        $this->whereIndex = count($query->wheres);
        $this->bindingIndex = count($query->getRawBindings()['where']);

        $types = array_keys($post::getModels());
        $this->bindingCount = count($types);
        $query->whereIn('type', $types);
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $post
     * @return void
     */
    public function remove(Builder $builder, Model $post)
    {
        $query = $builder->getQuery();

        unset($query->wheres[$this->whereIndex]);
        $query->wheres = array_values($query->wheres);

        $whereBindings = $query->getRawBindings()['where'];
        array_splice($whereBindings, $this->bindingIndex, $this->bindingCount);
        $query->setBindings(array_values($whereBindings));
    }
}
