<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Tobscure\JsonApi\Document;
use Zend\Diactoros\Response\JsonResponse;

class JsonApiResponse extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Document $document, $status = 200, array $headers = [], $encodingOptions = 15)
    {
        $headers['content-type'] = 'application/vnd.api+json';

        // The call to jsonSerialize prevents rare issues with json_encode() failing with a
        // syntax error even though Document implements the JsonSerializable interface.
        // See https://github.com/flarum/core/issues/685
        parent::__construct($document->jsonSerialize(), $status, $headers, $encodingOptions);
    }
}
