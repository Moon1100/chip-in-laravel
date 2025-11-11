# Chip-In Laravel

[![Latest Version](https://img.shields.io/github/v/release/Moon1100/chip-in-laravel)](https://github.com/Moon1100/chip-in-laravel/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)

A simple Laravel 12 package to integrate the [CHIP Collect API](https://docs.chip-in.asia/chip-collect/overview/introduction) for seamless payment creation, status checking, and webhook handling.

---

## 🚀 Features

- 🔧 Easy integration with CHIP Collect API
- 💳 Create new payment sessions
- 📦 Verify and query payment status
- 🌐 Webhook-ready for payment notifications
- ⚙️ Simple `.env` configuration
- 🧩 Clean and extensible API client

---

## 📦 Installation

Install via Composer:

```bash
composer require aliff/chip-in
```

---

## ⚙️ Configuration

### Step 1: Publish Configuration Files

```bash
php artisan vendor:publish --tag=chipin-config
```

This creates `config/chipin.php` in your application.

### Step 2: Set Environment Variables

Add the following to your `.env` file:

```env
CHIPIN_API_KEY=your_api_key_here
CHIPIN_BRAND_ID=your_brand_id_here
CHIPIN_MODE=sandbox
CHIPIN_BASE_URL=https://gate.chip-in.asia/api/v1/
CHIP_IN_CALLBACKHANDLER=App\Http\Controllers\PaymentController
```


### Step 3: Create a Payment Handler Controller

Create a controller to handle payment callbacks at `app/Http/Controllers/PaymentController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function handleChipInCallback(Request $request)
    {
        $data = $request->all();
        Log::info('💳 CHIP callback received', ['data' => $data]);

        // ✅ Extract Entity ID (stored in reference)
        $entityId = Arr::get($data, 'reference');

        // Fallback: try metadata
        if (! $entityId) {
            $metadata = Arr::get($data, 'metadata', []);
            $entityId = $metadata['entity_id'] ?? null;
        }

        if (! $entityId) {
            Log::warning('⚠️ Missing Entity ID in callback', ['data' => $data]);
            return response()->json(['error' => 'Missing Entity ID'], 400);
        }

        // ✅ Find your entity record (e.g., WillWasiat, Order, etc.)
        $entity = YourModel::find($entityId);

        if (! $entity) {
            Log::warning("⚠️ Entity not found for ID {$entityId}");
            return response()->json(['error' => 'Entity not found'], 404);
        }

        // ✅ Create or update Payment record
        $payment = Payment::updateOrCreate(
            ['chip_id' => Arr::get($data, 'id')],
            [
                'payable_id'   => $entity->id,
                'payable_type' => YourModel::class,
                'status'       => Arr::get($data, 'status'),
                'email'        => Arr::get($data, 'email', Arr::get($data, 'client.email')),
                'amount'       => Arr::get($data, 'payment.amount', Arr::get($data, 'amount')),
                'currency'     => Arr::get($data, 'payment.currency', 'MYR'),
                'reference'    => Arr::get($data, 'reference'),
                'brand_id'     => Arr::get($data, 'brand_id'),
                'payload'      => $data,
                'checkout_url' => Arr::get($data, 'checkout_url'),
                'paid_at'      => now(),
            ]
        );

        // ✅ Update entity status
        $entity->update([
            'payment_status' => $payment->status ?? 'pending',
        ]);

        Log::info("✅ Payment stored & linked", [
            'payment_id' => $payment->id,
            'entity_id' => $entity->id,
            'status' => $payment->status,
        ]);

        // ✅ If callback came from browser (user flow), redirect back to app
        if ($request->expectsJson() === false) {
            return redirect()
                ->route('entity.completed', ['id' => $entity->id])
                ->with('success', 'Your payment has been successfully processed!');
        }

        // ✅ For API/callback endpoint
        return response()->json(['success' => true]);
    }
}
```

---

## 📖 Usage Guide

### Step 1: Create a Payment Session

In your controller or service, inject the `Purchase` endpoint class:

```php
<?php

namespace App\Http\Controllers;

use Aliff\ChipIn\Http\Client;
use Aliff\ChipIn\Http\Endpoints\Purchase;
use Illuminate\Support\Facades\Crypt;

class OrderController extends Controller
{
    public function proceed()
    {
        $payload = [
            'client' => [
                'email' => $this->entity->user->email ?? auth()->user()->email,
            ],
            'purchase' => [
                'products' => [
                    [
                        'name' => 'Your Product Name',
                        'price' => 13424,  // Amount in cents (134.24)
                        'quantity' => 1,
                    ],
                ],
                'due_strict' => false,
            ],
            'reference' => $this->entity->id,  // ← Store your entity ID here
            'success_redirect' => route('entity.completed', [
                'id' => Crypt::encrypt($this->entity->id),
                'status' => 'success'
            ]),
            'failed_redirect' => route('entity.completed', [
                'id' => Crypt::encrypt($this->entity->id),
                'status' => 'failed'
            ]),
        ];

        $purchase = new Purchase(app(Client::class));

        return $purchase->createAndRedirect($payload);
    }
}
```

### Step 2: User Completes Payment

- User is redirected to CHIP's checkout page
- User enters payment details and completes payment
- CHIP processes the transaction

### Step 3: Handle Payment Callback

When payment is completed:

1. ✅ CHIP sends a webhook callback to `/chipin/callback`
2. ✅ The `ChipInController` receives and verifies the request
3. ✅ Your `PaymentController::handleChipInCallback()` is automatically called
4. ✅ Payment record is created/updated in your database
5. ✅ Your entity (Order, Subscription, etc.) status is updated

### Step 4: Display Success/Failed Pages

The package includes default views at:

- **Success**: `/chipin/success` → `resources/views/success.blade.php`
- **Failed**: `/chipin/failed` → `resources/views/failed.blade.php`

You can override these by publishing the views:

```bash
php artisan vendor:publish --tag=chipin-views
```

---

## 🔒 Security

### Webhook Verification

To verify webhook authenticity, ensure your webhook secret is configured in the CHIP dashboard and stored securely. The `ChipInController` will validate the `X-CHIP-Signature` header automatically.

---

## 📚 API Reference

### Purchase Class

#### `create(array $payload): array`

Create a payment without redirecting.

```php
$purchase = new Purchase(new Client());
$response = $purchase->create([
    'amount' => 10000,
    'reference' => 'ORDER-123',
    'client' => ['email' => 'user@example.com'],
]);

$checkoutUrl = $response['checkout_url'];
```

#### `createAndRedirect(array $payload): RedirectResponse`

Create a payment and immediately redirect to CHIP checkout.

```php
return $purchase->createAndRedirect([
    'amount' => 10000,
    'reference' => 'ORDER-123',
    'client' => ['email' => 'user@example.com'],
]);
```

#### `handleCallback(array $data): array`

Normalize callback data from CHIP webhook.

```php
$normalized = $purchase->handleCallback($chipWebhookData);
// Returns: ['id', 'status', 'email', 'amount', 'reference', 'checkout_url', 'raw']
```

### Client Class

#### `post(string $endpoint, array $data): array`

Send POST request to CHIP API.

```php
$client = new Client();
$response = $client->post('purchases/', $payload);
```

#### `get(string $endpoint): array`

Send GET request to CHIP API.

```php
$response = $client->get('purchases/{id}/');
```

---

## 📋 Callback Data Structure

When CHIP sends a webhook callback, the `PaymentController::handleChipInCallback()` receives data in the following format:

```php
[
    'id' => 'chip_payment_id',
    'status' => 'paid|failed|cancelled',
    'email' => 'customer@example.com',
    'amount' => 13424,  // in cents
    'currency' => 'MYR',
    'reference' => 'your_entity_id',  // Your stored reference
    'brand_id' => 'your_brand_id',
    'payment' => [
        'amount' => 13424,
        'currency' => 'MYR',
    ],
    'client' => [
        'email' => 'customer@example.com',
    ],
    'checkout_url' => 'https://...',
    'metadata' => [
        // Your custom metadata
    ],
]
```

---

## 💾 Payment Database Table

Recommended schema for your `payments` table:

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->string('chip_id')->unique();
    $table->morphs('payable');  // payable_id & payable_type
    $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
    $table->string('email')->nullable();
    $table->decimal('amount', 12, 2);
    $table->string('currency', 3)->default('MYR');
    $table->string('reference')->nullable();
    $table->uuid('brand_id')->nullable();
    $table->json('payload')->nullable();
    $table->text('checkout_url')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

---

## 🛠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| `CHIP API Error (401)` | Verify `CHIPIN_API_KEY` and `CHIPIN_BRAND_ID` in `.env` |
| `Callback not received` | Ensure your webhook URL is registered in CHIP dashboard |
| `No checkout_url returned` | Check API response payload and CHIP API status |
| `Payment reference not found` | Ensure `reference` field matches your entity ID |
| `Callback verification failed` | Verify webhook secret configuration |

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
