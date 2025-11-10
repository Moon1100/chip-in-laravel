<?php

namespace Aliff\ChipIn\Http\Endpoints;

use Aliff\ChipIn\Http\Client;
use Aliff\ChipIn\Http\Exceptions\ApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Redirect;
use Livewire\Features\SupportRedirects\Redirector as LivewireRedirector;

class Purchase
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new purchase on CHIP API.
     *
     *
     * @throws ApiException
     */
    public function create(array $payload): array
    {
        // Merge default CHIP fields (brand, flags)
        $payload = array_merge([
            'brand_id' => config('chipin.brand_id'),
            'send_receipt' => false,
            'skip_capture' => false,
            'force_recurring' => false,
            // 'success_redirect' => route('chipin.success'),
            // 'failure_redirect' => route('chipin.failed'),
            'success_callback' => route('chipin.callback'),
        ], $payload);

        return $this->client->post('purchases/', $payload);
    }

    /**
     * Create a purchase and redirect user to CHIP checkout page.
     *
     * @return RedirectResponse
     *
     * @throws ApiException
     */
    public function createAndRedirect(array $payload): RedirectResponse|Redirector|LivewireRedirector
    {
        $response = $this->create($payload);

        if (empty($response['checkout_url'])) {
            throw new ApiException('CHIP API did not return a checkout_url. Response: '.json_encode($response));
        }

        // Works for both Laravel + Livewire
        return redirect()->away($response['checkout_url']);
    }

    /**
     * Handle callback (webhook or redirect confirmation).
     */
    public function handleCallback(array $data): array
    {
        // Normalize data for your app to handle
        return [
            'id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
            'email' => $data['client']['email'] ?? null,
            'amount' => $data['payment']['amount'] ?? null,
            'reference' => $data['reference'] ?? null,
            'checkout_url' => $data['checkout_url'] ?? null,
            'raw' => $data,
        ];
    }
}
