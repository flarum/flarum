<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Api\Controller\ShowForumController;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Event\ConfigureApiController;
use Flarum\Event\GetApiRelationship;
use Flarum\Event\PrepareApiAttributes;
use Flarum\Event\PrepareApiData;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class AddForumTagsRelationship
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetApiRelationship::class, [$this, 'getApiRelationship']);
        $events->listen(PrepareApiData::class, [$this, 'loadTagsRelationship']);
        $events->listen(ConfigureApiController::class, [$this, 'includeTagsRelationship']);
        $events->listen(PrepareApiAttributes::class, [$this, 'prepareApiAttributes']);
    }

    /**
     * @param GetApiRelationship $event
     * @return \Tobscure\JsonApi\Relationship|null
     */
    public function getApiRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(ForumSerializer::class, 'tags')) {
            return $event->serializer->hasMany($event->model, 'Flarum\Tags\Api\Serializer\TagSerializer', 'tags');
        }
    }

    /**
     * @param PrepareApiData $event
     */
    public function loadTagsRelationship(PrepareApiData $event)
    {
        // Expose the complete tag list to clients by adding it as a
        // relationship to the /api/forum endpoint. Since the Forum model
        // doesn't actually have a tags relationship, we will manually load and
        // assign the tags data to it using an event listener.
        if ($event->isController(ShowForumController::class)) {
            $event->data['tags'] = Tag::whereVisibleTo($event->actor)->withStateFor($event->actor)->with('lastDiscussion')->get();
        }
    }

    /**
     * @param ConfigureApiController $event
     */
    public function includeTagsRelationship(ConfigureApiController $event)
    {
        if ($event->isController(ShowForumController::class)) {
            $event->addInclude(['tags', 'tags.lastDiscussion', 'tags.parent']);
        }
    }

    /**
     * @param PrepareApiAttributes $event
     */
    public function prepareApiAttributes(PrepareApiAttributes $event)
    {
        if ($event->isSerializer(ForumSerializer::class)) {
            $event->attributes['minPrimaryTags'] = $this->settings->get('flarum-tags.min_primary_tags');
            $event->attributes['maxPrimaryTags'] = $this->settings->get('flarum-tags.max_primary_tags');
            $event->attributes['minSecondaryTags'] = $this->settings->get('flarum-tags.min_secondary_tags');
            $event->attributes['maxSecondaryTags'] = $this->settings->get('flarum-tags.max_secondary_tags');
        }
    }
}
