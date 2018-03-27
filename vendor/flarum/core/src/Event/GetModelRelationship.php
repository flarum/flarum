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

use Flarum\Database\AbstractModel;

/**
 * The `GetModelRelationship` event is called to retrieve Relation object for a
 * model. Listeners should return an Eloquent Relation object.
 */
class GetModelRelationship
{
    /**
     * @var AbstractModel
     */
    public $model;

    /**
     * @var string
     */
    public $relationship;

    /**
     * @param AbstractModel $model
     * @param string $relationship
     */
    public function __construct(AbstractModel $model, $relationship)
    {
        $this->model = $model;
        $this->relationship = $relationship;
    }

    /**
     * @param string $model
     * @param string $relationship
     * @return bool
     */
    public function isRelationship($model, $relationship)
    {
        return $this->model instanceof $model && $this->relationship === $relationship;
    }
}
