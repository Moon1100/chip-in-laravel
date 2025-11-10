<?php

namespace Aliff\ChipIn\Http;

use Illuminate\Support\Facades\Http;
use Aliff\ChipIn\Http\Exceptions\ApiException;
use Illuminate\Support\Facades\Log;

class Client
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $brandId;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('chipin.base_url', 'https://gate.chip-in.asia/api/v1/'), '/') . '/';
        $this->apiKey  = config('chipin.api_key', env('CHIP_API_KEY'));
        $this->brandId = config('chipin.brand_id', env('CHIP_BRAND_ID'));
    }

    /**
     * Send a POST request to the CHIP API.
     */
    public function post(string $endpoint, array $data = []): array
    {
        // Ensure brand_id is injected automatically
        if (!isset($data['brand_id']) && $this->brandId) {
            $data['brand_id'] = $this->brandId;
        }

        $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . $endpoint, $data);

        // Optional: Log API responses (useful in production)
        Log::channel('stack')->info('CHIP API Request', [
            'endpoint' => $endpoint,
            'payload'  => $data,
            'status'   => $response->status(),
        ]);

        if (!$response->successful()) {
            throw new ApiException(
                sprintf(
                    "CHIP API Error (%s): %s",
                    $response->status(),
                    $response->body()
                ),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Send a GET request to the CHIP API.
     */
    public function get(string $endpoint): array
    {
        $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ])->get($this->baseUrl . $endpoint);

        if (!$response->successful()) {
            throw new ApiException(
                sprintf(
                    "CHIP API Error (%s): %s",
                    $response->status(),
                    $response->body()
                ),
                $response->status()
            );
        }

        return $response->json();
    }
}
