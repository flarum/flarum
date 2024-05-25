<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

$site = require '../site.php';

/*
|-------------------------------------------------------------------------------
| Accept incoming HTTP requests
|-------------------------------------------------------------------------------
|
| Every HTTP request pointed to the web server that cannot be served by simply
| responding with one of the files in the "public" directory will be sent to
| this file. Now is the time to boot up Flarum's internal HTTP server, which
| will try its best to interpret the request and return the appropriate
| response, which could be a JSON document (for API responses) or a lot of HTML.
|
*/

$server = new Flarum\Http\Server($site);
$server->listen();
