<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Post\CommentPost;
use Flarum\Core\Repository\PostRepository;
use Flarum\Flags\Flag;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class CreateFlagHandler
{
    use AssertPermissionTrait;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @param PostRepository $posts
     */
    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param CreateFlag $command
     * @return Flag
     * @throws InvalidParameterException
     */
    public function handle(CreateFlag $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $postId = array_get($data, 'relationships.post.data.id');
        $post = $this->posts->findOrFail($postId, $actor);

        if (! ($post instanceof CommentPost)) {
            throw new InvalidParameterException;
        }

        $this->assertCan($actor, 'flag', $post);

        Flag::unguard();

        $flag = Flag::firstOrNew([
            'post_id' => $post->id,
            'user_id' => $actor->id
        ]);

        $flag->post_id = $post->id;
        $flag->user_id = $actor->id;
        $flag->type = 'user';
        $flag->reason = array_get($data, 'attributes.reason');
        $flag->reason_detail = array_get($data, 'attributes.reasonDetail');
        $flag->time = time();

        $flag->save();

        return $flag;
    }
}
