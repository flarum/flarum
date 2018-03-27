<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The `ScopeModelVisibility` event allows constraints to be applied in a query
 * to fetch a model, effectively scoping that model's visibility to the user.
 */
class ScopeModelVisibility
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var Builder
     */
    public $query;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param Model $model
     * @param Builder $query
     * @param User $actor
     */
    public function __construct(Model $model, Builder $query, User $actor)
    {
        $this->model = $model;
        $this->query = $query;
        $this->actor = $actor;
    }
}
