<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

interface DataProviderInterface
{
    public function getDatabaseConfiguration();

    public function getBaseUrl();

    public function getAdminUser();

    public function getSettings();
}
