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

class DeleteTag
{
    /**
     * The ID of the tag to delete.
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
     * Any other tag input associated with the action. This is unused by
     * default, but may be used by extensions.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $tagId The ID of the tag to delete.
     * @param User $actor The user performing the action.
     * @param array $data Any other tag input associated with the action. This
     *     is unused by default, but may be used by extensions.
     */
    public function __construct($tagId, User $actor, array $data = [])
    {
        $this->tagId = $tagId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
