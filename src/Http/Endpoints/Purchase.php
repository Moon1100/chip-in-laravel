<?php

namespace Aliff\ChipIn\Http\Endpoints;

use Aliff\ChipIn\Http\Client;

class Purchase
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(array $payload)
    {
        // Example endpoint: purchases
        return $this->client->post('purchases', $payload);
    }
}
