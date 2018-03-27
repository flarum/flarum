<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Core\Post;
use Flarum\Core\Support\ScopeVisibilityTrait;
use Flarum\Core\User;
use Flarum\Database\AbstractModel;

class Flag extends AbstractModel
{
    use ScopeVisibilityTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'flags';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['time'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
