<?php

use Flarum\Event\ConfigureClientView;
use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events) {
    $events->listen(ConfigureClientView::class, function (ConfigureClientView $event) {
        if ($event->isForum()) {
            $event->addAssets([
                __DIR__ . '/js/forum/dist/extension.js',
                __DIR__ . '/less/forum/extension.less',
            ]);
            $event->addBootstrapper('romanzpolski/shawTheme/main');
        }
    });
    $events->listen(PostWillBeSaved::class, function (PostWillBeSaved $event) {
        $event->post->content = 'This is not what I wrote!';
    });
};