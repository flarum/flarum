<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Approval\Access;

use Flarum\Core\Access\AbstractPolicy;
use Flarum\Core\User;
use Flarum\Tags\Tag;

class TagPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = Tag::class;

    /**
     * @param User $actor
     * @param Tag $tag
     * @return bool|null
     */
    public function addToDiscussion(User $actor, Tag $tag)
    {
        $disallowedTags = Tag::getIdsWhereCannot($actor, 'discussion.startWithoutApproval');

        if (in_array($tag->id, $disallowedTags)) {
            return false;
        }
    }
}
