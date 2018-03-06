<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Tags\Event\TagWillBeSaved;
use Flarum\Tags\TagRepository;
use Flarum\Tags\TagValidator;

class EditTagHandler
{
    use AssertPermissionTrait;

    /**
     * @var TagRepository
     */
    protected $tags;

    /**
     * @var TagValidator
     */
    protected $validator;

    /**
     * @param TagRepository $tags
     * @param TagValidator $validator
     */
    public function __construct(TagRepository $tags, TagValidator $validator)
    {
        $this->tags = $tags;
        $this->validator = $validator;
    }

    /**
     * @param EditTag $command
     * @return \Flarum\Tags\Tag
     * @throws \Flarum\Core\Exception\PermissionDeniedException
     */
    public function handle(EditTag $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $tag = $this->tags->findOrFail($command->tagId, $actor);

        $this->assertCan($actor, 'edit', $tag);

        $attributes = array_get($data, 'attributes', []);

        if (isset($attributes['name'])) {
            $tag->name = $attributes['name'];
        }

        if (isset($attributes['slug'])) {
            $tag->slug = $attributes['slug'];
        }

        if (isset($attributes['description'])) {
            $tag->description = $attributes['description'];
        }

        if (isset($attributes['color'])) {
            $tag->color = $attributes['color'];
        }

        if (isset($attributes['isHidden'])) {
            $tag->is_hidden = (bool) $attributes['isHidden'];
        }

        if (isset($attributes['isRestricted'])) {
            $tag->is_restricted = (bool) $attributes['isRestricted'];
        }

        event(new TagWillBeSaved($tag, $actor, $data));

        $this->validator->assertValid($tag->getDirty());

        $tag->save();

        return $tag;
    }
}
