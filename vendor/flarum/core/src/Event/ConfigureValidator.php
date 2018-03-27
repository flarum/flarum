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

use Flarum\Core\Validator\AbstractValidator;
use Illuminate\Validation\Validator;

/**
 * The `ConfigureModelValidator` event is called when a validator instance for a
 * model is being built. This event can be used to add custom rules/extensions
 * to the validator for when validation takes place.
 */
class ConfigureValidator
{
    /**
     * @var AbstractValidator
     */
    public $type;

    /**
     * @var Validator
     */
    public $validator;

    /**
     * @param AbstractValidator $type
     * @param Validator $validator
     */
    public function __construct(AbstractValidator $type, Validator $validator)
    {
        $this->type = $type;
        $this->validator = $validator;
    }
}
