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

use Flarum\Core\Repository\UserRepository;
use Flarum\Event\ConfigureFormatter;
use Flarum\Event\ConfigureFormatterParser;
use Flarum\Event\ConfigureFormatterRenderer;
use Flarum\Forum\UrlGenerator;
use Illuminate\Contracts\Events\Dispatcher;

class FormatUserMentions
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UserRepository $users
     * @param UrlGenerator $url
     */
    public function __construct(UserRepository $users, UrlGenerator $url)
    {
        $this->users = $users;
        $this->url = $url;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureFormatter::class, [$this, 'configure']);
        $events->listen(ConfigureFormatterParser::class, [$this, 'parse']);
        $events->listen(ConfigureFormatterRenderer::class, [$this, 'render']);
    }

    /**
     * @param ConfigureFormatter $event
     */
    public function configure(ConfigureFormatter $event)
    {
        $configurator = $event->configurator;

        $tagName = 'USERMENTION';

        $tag = $configurator->tags->add($tagName);
        $tag->attributes->add('username');
        $tag->attributes->add('id')->filterChain->append('#uint');
        $tag->attributes['id']->required = false;

        $tag->template = '<a href="{$PROFILE_URL}{@username}" class="UserMention">@<xsl:value-of select="@username"/></a>';
        $tag->filterChain->prepend([static::class, 'addId'])
            ->addParameterByName('userRepository')
            ->setJS('function() { return true; }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)(?!#)/i', $tagName);
    }

    /**
     * @param ConfigureFormatterParser $event
     */
    public function parse(ConfigureFormatterParser $event)
    {
        $event->parser->registeredVars['userRepository'] = $this->users;
    }

    /**
     * @param ConfigureFormatterRenderer $event
     */
    public function render(ConfigureFormatterRenderer $event)
    {
        $event->renderer->setParameter('PROFILE_URL', $this->url->toRoute('user', ['username' => '']));
    }

    /**
     * @param $tag
     * @param UserRepository $users
     * @return bool
     */
    public static function addId($tag, UserRepository $users)
    {
        if ($id = $users->getIdForUsername($tag->getAttribute('username'))) {
            $tag->setAttribute('id', $id);

            return true;
        }
    }
}
