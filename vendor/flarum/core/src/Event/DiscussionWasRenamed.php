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

use Flarum\Core\Discussion;
use Flarum\Core\User;

class DiscussionWasRenamed
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @var string
     */
    public $oldTitle;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param Discussion $discussion
     * @param User $actor
     * @param string $oldTitle
     */
    public function __construct(Discussion $discussion, $oldTitle, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->oldTitle = $oldTitle;
        $this->actor = $actor;
    }
}
