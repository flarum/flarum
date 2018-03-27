<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Formatter\Formatter;
use Flarum\Http\WebApp\AbstractWebApp;
use Flarum\Http\WebApp\WebAppAssetsFactory;
use Flarum\Http\WebApp\WebAppViewFactory;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;

class WebApp extends AbstractWebApp
{
    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        WebAppAssetsFactory $assets,
        WebAppViewFactory $view,
        SettingsRepositoryInterface $settings,
        LocaleManager $locales,
        Formatter $formatter
    ) {
        parent::__construct($assets, $view, $settings, $locales);

        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getView()
    {
        $view = parent::getView();

        $view->getJs()->addString(function () {
            return $this->formatter->getJs();
        });

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'forum';
    }
}
