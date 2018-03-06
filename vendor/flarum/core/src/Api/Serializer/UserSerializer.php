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

class UserSerializer extends UserBasicSerializer
{
    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @param Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        $gate = $this->gate->forUser($this->actor);

        $canEdit = $gate->allows('edit', $user);

        $attributes += [
            'bio'              => $user->bio,
            'joinTime'         => $this->formatDate($user->join_time),
            'discussionsCount' => (int) $user->discussions_count,
            'commentsCount'    => (int) $user->comments_count,
            'canEdit'          => $canEdit,
            'canDelete'        => $gate->allows('delete', $user),
        ];

        if ($user->getPreference('discloseOnline')) {
            $attributes += [
                'lastSeenTime' => $this->formatDate($user->last_seen_time)
            ];
        }

        if ($canEdit || $this->actor->id === $user->id) {
            $attributes += [
                'isActivated' => (bool) $user->is_activated,
                'email'       => $user->email
            ];
        }

        return $attributes;
    }
}
