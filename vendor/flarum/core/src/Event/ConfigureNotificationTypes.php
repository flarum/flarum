<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\Core\Notification\BlueprintInterface;
use InvalidArgumentException;
use ReflectionClass;

class ConfigureNotificationTypes
{
    /**
     * @var array
     */
    private $blueprints;

    /**
     * @var array
     */
    private $serializers;

    /**
     * @param array $blueprints
     * @param array $serializers
     */
    public function __construct(array &$blueprints, array &$serializers = [])
    {
        $this->blueprints = &$blueprints;
        $this->serializers = &$serializers;
    }

    /**
     * @param string $blueprint
     * @param string $serializer
     * @param array $enabledByDefault
     */
    public function add($blueprint, $serializer, $enabledByDefault = [])
    {
        if (! (new ReflectionClass($blueprint))->implementsInterface(BlueprintInterface::class)) {
            throw new InvalidArgumentException(
                'Notification blueprint '.$blueprint.' must implement '.BlueprintInterface::class
            );
        }

        $this->blueprints[$blueprint] = $enabledByDefault;

        $this->serializers[$blueprint::getType()] = $serializer;
    }
}
