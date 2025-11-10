<?php

namespace Aliff\ChipIn\Http;

use Illuminate\Support\Facades\Http;
use Aliff\ChipIn\Http\Exceptions\ApiException;

class Client
{
    protected $baseUrl;
    protected $apiKey;
    protected $brandId;

    public function __construct()
    {
        $config = config('chipin');
        $this->baseUrl = $config['base_url'];
        $this->apiKey  = $config['api_key'];
        $this->brandId = $config['brand_id'];
    }

    public function post($endpoint, array $data = [])
    {
        $response = Http::withToken($this->apiKey)
                        ->post($this->baseUrl . $endpoint, array_merge($data, [
                            'brand_id' => $this->brandId
                        ]));

        if (!$response->successful()) {
            throw new ApiException('CHIP API Error: '.$response->body(), $response->status());
        }

        return $response->json();
    }
}
