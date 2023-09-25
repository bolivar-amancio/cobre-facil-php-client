<?php

namespace CobreFacil\Resources;

use CobreFacil\ApiOperations\Create;

class Subscription extends ApiResource
{
    use Create;

    protected $endpoint = 'subscriptions';
}
