<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Post\CommentPost;
use Flarum\Core\Repository\PostRepository;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\Validator\PostValidator;
use Flarum\Event\PostWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;

class EditPostHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var PostRepository
     */
    protected $posts;

    /**
     * @var PostValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param PostRepository $posts
     * @param PostValidator $validator
     */
    public function __construct(Dispatcher $events, PostRepository $posts, PostValidator $validator)
    {
        $this->events = $events;
        $this->posts = $posts;
        $this->validator = $validator;
    }

    /**
     * @param EditPost $command
     * @return \Flarum\Core\Post
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(EditPost $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $post = $this->posts->findOrFail($command->postId, $actor);

        if ($post instanceof CommentPost) {
            $attributes = array_get($data, 'attributes', []);

            if (isset($attributes['content'])) {
                $this->assertCan($actor, 'edit', $post);

                $post->revise($attributes['content'], $actor);
            }

            if (isset($attributes['isHidden'])) {
                $this->assertCan($actor, 'edit', $post);

                if ($attributes['isHidden']) {
                    $post->hide($actor);
                } else {
                    $post->restore();
                }
            }
        }

        $this->events->fire(
            new PostWillBeSaved($post, $actor, $data)
        );

        $this->validator->assertValid($post->getDirty());

        $post->save();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
