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

use Exception;
use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Discussion;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Core\Validator\DiscussionValidator;
use Flarum\Event\DiscussionWillBeSaved;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

class StartDiscussionHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var BusDispatcher
     */
    protected $bus;

    /**
     * @var DiscussionValidator
     */
    protected $validator;

    /**
     * @param EventDispatcher $events
     * @param BusDispatcher $bus
     * @param DiscussionValidator $validator
     */
    public function __construct(EventDispatcher $events, BusDispatcher $bus, DiscussionValidator $validator)
    {
        $this->events = $events;
        $this->bus = $bus;
        $this->validator = $validator;
    }

    /**
     * @param StartDiscussion $command
     * @return mixed
     * @throws Exception
     */
    public function handle(StartDiscussion $command)
    {
        $actor = $command->actor;
        $data = $command->data;
        $ipAddress = $command->ipAddress;

        $this->assertCan($actor, 'startDiscussion');

        // Create a new Discussion entity, persist it, and dispatch domain
        // events. Before persistence, though, fire an event to give plugins
        // an opportunity to alter the discussion entity based on data in the
        // command they may have passed through in the controller.
        $discussion = Discussion::start(
            array_get($data, 'attributes.title'),
            $actor
        );

        $this->events->fire(
            new DiscussionWillBeSaved($discussion, $actor, $data)
        );

        $this->validator->assertValid($discussion->getAttributes());

        $discussion->save();

        // Now that the discussion has been created, we can add the first post.
        // We will do this by running the PostReply command.
        try {
            $post = $this->bus->dispatch(
                new PostReply($discussion->id, $actor, $data, $ipAddress)
            );
        } catch (Exception $e) {
            $discussion->delete();

            throw $e;
        }

        // Before we dispatch events, refresh our discussion instance's
        // attributes as posting the reply will have changed some of them (e.g.
        // last_time.)
        $discussion->setRawAttributes($post->discussion->getAttributes(), true);
        $discussion->setStartPost($post);
        $discussion->setLastPost($post);

        $this->dispatchEventsFor($discussion, $actor);

        $discussion->save();

        return $discussion;
    }
}
