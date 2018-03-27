<?php

/*
 * This file is part of JSON-API.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tobscure\Tests\JsonApi;

use Tobscure\JsonApi\Util;

class UtilTest extends AbstractTestCase
{
    public function testParseRelationshipPaths()
    {
        $this->assertEquals(
            ['user' => ['employer', 'employer.country'], 'comments' => []],
            Util::parseRelationshipPaths(['user', 'user.employer', 'user.employer.country', 'comments'])
        );

        $this->assertEquals(
            ['user' => ['employer.country']],
            Util::parseRelationshipPaths(['user.employer.country'])
        );
    }
}
