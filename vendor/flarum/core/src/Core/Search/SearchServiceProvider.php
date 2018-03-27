<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search;

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;

class SearchServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Search\Discussion\Fulltext\DriverInterface',
            'Flarum\Core\Search\Discussion\Fulltext\MySqlFulltextDriver'
        );

        $this->registerDiscussionGambits();

        $this->registerUserGambits();
    }

    public function registerUserGambits()
    {
        $this->app->when('Flarum\Core\Search\User\UserSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                $gambits->setFulltextGambit('Flarum\Core\Search\User\Gambit\FulltextGambit');
                $gambits->add('Flarum\Core\Search\User\Gambit\EmailGambit');
                $gambits->add('Flarum\Core\Search\User\Gambit\GroupGambit');

                $app->make('events')->fire(
                    new ConfigureUserGambits($gambits)
                );

                return $gambits;
            });
    }

    public function registerDiscussionGambits()
    {
        $this->app->when('Flarum\Core\Search\Discussion\DiscussionSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                $gambits->setFulltextGambit('Flarum\Core\Search\Discussion\Gambit\FulltextGambit');
                $gambits->add('Flarum\Core\Search\Discussion\Gambit\AuthorGambit');
                $gambits->add('Flarum\Core\Search\Discussion\Gambit\CreatedGambit');
                $gambits->add('Flarum\Core\Search\Discussion\Gambit\HiddenGambit');
                $gambits->add('Flarum\Core\Search\Discussion\Gambit\UnreadGambit');

                $app->make('events')->fire(
                    new ConfigureDiscussionGambits($gambits)
                );

                return $gambits;
            });
    }
}
