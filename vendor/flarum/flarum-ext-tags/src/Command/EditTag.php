<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\Core\User;

class EditTag
{
    /**
     * The ID of the tag to edit.
     *
     * @var int
     */
    public $tagId;

    /**
     * The user performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * The attributes to update on the tag.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $tagId The ID of the tag to edit.
     * @param User $actor The user performing the action.
     * @param array $data The attributes to update on the tag.
     */
    public function __construct($tagId, User $actor, array $data)
    {
        $this->tagId = $tagId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
