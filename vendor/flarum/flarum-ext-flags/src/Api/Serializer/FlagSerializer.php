<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Api\Serializer\UserBasicSerializer;

class FlagSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'flags';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($flag)
    {
        return [
            'type'         => $flag->type,
            'reason'       => $flag->reason,
            'reasonDetail' => $flag->reason_detail,
            'time'         => $this->formatDate($flag->time),
        ];
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function post($flag)
    {
        return $this->hasOne($flag, PostSerializer::class);
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function user($flag)
    {
        return $this->hasOne($flag, UserBasicSerializer::class);
    }
}
