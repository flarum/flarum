<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Core\Post\CommentPost;
use Flarum\Event\ConfigureFormatter;
use Flarum\Event\ConfigureFormatterRenderer;
use Flarum\Forum\UrlGenerator;
use Illuminate\Contracts\Events\Dispatcher;

class FormatPostMentions
{
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UrlGenerator $url
     */
    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureFormatter::class, [$this, 'configure']);
        $events->listen(ConfigureFormatterRenderer::class, [$this, 'render']);
    }

    /**
     * @param ConfigureFormatter $event
     */
    public function configure(ConfigureFormatter $event)
    {
        $configurator = $event->configurator;

        $tagName = 'POSTMENTION';

        $tag = $configurator->tags->add($tagName);

        $tag->attributes->add('username');
        $tag->attributes->add('number')->filterChain->append('#uint');
        $tag->attributes->add('discussionid')->filterChain->append('#uint');
        $tag->attributes->add('id')->filterChain->append('#uint');
        $tag->attributes['number']->required = false;
        $tag->attributes['discussionid']->required = false;

        $tag->template = '<a href="{$DISCUSSION_URL}{@discussionid}/{@number}" class="PostMention" data-id="{@id}"><xsl:value-of select="@username"/></a>';

        $tag->filterChain
            ->prepend([static::class, 'addId'])
            ->setJS('function() { return true; }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)#(?<id>\d+)/i', $tagName);
    }

    /**
     * @param ConfigureFormatterRenderer $event
     */
    public function render(ConfigureFormatterRenderer $event)
    {
        $event->renderer->setParameter('DISCUSSION_URL', $this->url->toRoute('discussion', ['id' => '']));
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function addId($tag)
    {
        $post = CommentPost::find($tag->getAttribute('id'));

        if ($post) {
            $tag->setAttribute('discussionid', (int) $post->discussion_id);
            $tag->setAttribute('number', (int) $post->number);

            return true;
        }
    }
}
