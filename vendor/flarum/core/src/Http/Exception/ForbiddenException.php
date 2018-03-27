<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Exception;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct($message = null, $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
