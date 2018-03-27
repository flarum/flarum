<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Core\Validator\AbstractValidator;

class TagValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'name' => ['required'],
        'slug' => ['required', 'unique:tags'],
        'is_hidden' => ['bool'],
        'description' => ['string', 'max:700'],
        'color' => ['regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
    ];
}
