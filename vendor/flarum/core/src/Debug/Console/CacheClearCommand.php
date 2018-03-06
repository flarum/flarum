<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Debug\Console;

use Flarum\Admin\WebApp as AdminWebApp;
use Flarum\Console\Command\AbstractCommand;
use Flarum\Forum\WebApp as ForumWebApp;
use Illuminate\Contracts\Cache\Store;

class CacheClearCommand extends AbstractCommand
{
    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * @var \Flarum\Forum\WebApp
     */
    protected $forum;

    /**
     * @var \Flarum\Admin\WebApp
     */
    protected $admin;

    /**
     * @param Store $cache
     * @param ForumWebApp $forum
     * @param AdminWebApp $admin
     */
    public function __construct(Store $cache, ForumWebApp $forum, AdminWebApp $admin)
    {
        $this->cache = $cache;
        $this->forum = $forum;
        $this->admin = $admin;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Remove all temporary and generated files');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Clearing the cache...');

        $this->forum->getAssets()->flush();
        $this->admin->getAssets()->flush();

        $this->cache->flush();
    }
}
