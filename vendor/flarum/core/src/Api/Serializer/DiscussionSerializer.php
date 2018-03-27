<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Core\Access\Gate;
use Flarum\Core\Discussion;

class DiscussionSerializer extends DiscussionBasicSerializer
{
    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @param \Flarum\Core\Access\Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($discussion)
    {
        $gate = $this->gate->forUser($this->actor);

        $attributes = parent::getDefaultAttributes($discussion) + [
            'commentsCount'     => (int) $discussion->comments_count,
            'participantsCount' => (int) $discussion->participants_count,
            'startTime'         => $this->formatDate($discussion->start_time),
            'lastTime'          => $this->formatDate($discussion->last_time),
            'lastPostNumber'    => (int) $discussion->last_post_number,
            'canReply'          => $gate->allows('reply', $discussion),
            'canRename'         => $gate->allows('rename', $discussion),
            'canDelete'         => $gate->allows('delete', $discussion),
            'canHide'           => $gate->allows('hide', $discussion)
        ];

        if ($discussion->hide_time) {
            $attributes['isHidden'] = true;
            $attributes['hideTime'] = $this->formatDate($discussion->hide_time);
        }

        Discussion::setStateUser($this->actor);

        if ($state = $discussion->state) {
            $attributes += [
                'readTime'   => $this->formatDate($state->read_time),
                'readNumber' => (int) $state->read_number
            ];
        }

        return $attributes;
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function hideUser($discussion)
    {
        return $this->hasOne($discussion, 'Flarum\Api\Serializer\UserSerializer');
    }
}
