<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Core\Discussion;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Repository\NotificationRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListNotificationsController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\NotificationSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = [
        'sender',
        'subject',
        'subject.discussion'
    ];

    /**
     * {@inheritdoc}
     */
    public $limit = 10;

    /**
     * @var \Flarum\Core\Repository\NotificationRepository
     */
    protected $notifications;

    /**
     * @param \Flarum\Core\Repository\NotificationRepository $notifications
     */
    public function __construct(NotificationRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        if ($actor->isGuest()) {
            throw new PermissionDeniedException;
        }

        $actor->markNotificationsAsRead()->save();

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $notifications = $this->notifications->findByUser($actor, $limit, $offset)
            ->load(array_diff($include, ['subject.discussion']))
            ->all();

        if (in_array('subject.discussion', $include)) {
            $this->loadSubjectDiscussions($notifications);
        }

        return $notifications;
    }

    /**
     * @param \Flarum\Core\Notification[] $notifications
     */
    private function loadSubjectDiscussions(array $notifications)
    {
        $ids = [];

        foreach ($notifications as $notification) {
            if ($notification->subject && $notification->subject->discussion_id) {
                $ids[] = $notification->subject->discussion_id;
            }
        }

        $discussions = Discussion::find(array_unique($ids));

        foreach ($notifications as $notification) {
            if ($notification->subject && $notification->subject->discussion_id) {
                $notification->subject->setRelation('discussion', $discussions->find($notification->subject->discussion_id));
            }
        }
    }
}
