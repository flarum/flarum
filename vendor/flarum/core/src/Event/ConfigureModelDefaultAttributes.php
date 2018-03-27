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

class ConfigureModelDefaultAttributes
{
    /**
     * @var AbstractModel
     */
    public $model;

    /**
     * @var array
     */
    public $attributes;

    /**
     * @param AbstractModel $model
     * @param array $attributes
     */
    public function __construct(AbstractModel $model, array &$attributes)
    {
        $this->model = $model;
        $this->attributes = &$attributes;
    }

    /**
     * @param string $model
     * @return bool
     */
    public function isModel($model)
    {
        return $this->model instanceof $model;
    }
}
