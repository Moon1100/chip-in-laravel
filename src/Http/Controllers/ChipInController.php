<?php

namespace Aliff\ChipIn\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;
use Aliff\ChipIn\Http\Endpoints\Purchase;

class ChipInController extends Controller
{
    public function success(Request $request)
    {
        // ✅ Redirect or show payment success page
        return view('chipin::success');
    }

    public function failed(Request $request)
    {
        // ⚠️ Show failure page or log it
        return view('chipin::failed');
    }

    /**
     * Handle the CHIP payment callback / webhook.
     */
   public function callback(Request $request)
{
    Log::info('CHIP callback received', [
        'headers' => $request->headers->all(),
        'payload' => $request->all(),
    ]);

    // ✅ Verify callback signature
    if (! $this->verifyRequest($request)) {
        Log::warning('CHIP callback verification failed.');
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $purchase = new Purchase(app(\Aliff\ChipIn\Http\Client::class));
    $data = $purchase->handleCallback($request->all());

    try {
        // 🔧 Dynamically call the configured callback handler
        $handlerClass = config('chipin.callback_handler');

        if ($handlerClass && class_exists($handlerClass)) {
            $handler = app($handlerClass);

            if (method_exists($handler, 'handleChipInCallback')) {
                $handler->handleChipInCallback($data);
                Log::info("✅ Callback handled by {$handlerClass}");
            } else {
                Log::warning("⚠️ {$handlerClass} does not implement handleChipInCallback()");
            }
        } else {
            Log::warning('⚠️ No valid callback handler configured.');
        }

        // ✅ Log payment status
        switch ($data['status']) {
            case 'paid':
                Log::info("✅ Payment successful for ID: {$data['id']}");
                break;
            case 'failed':
            case 'cancelled':
                Log::warning("⚠️ Payment failed or cancelled for ID: {$data['id']}");
                break;
            default:
                Log::info("ℹ️ Payment callback received with status: {$data['status']}");
                break;
        }

    } catch (\Throwable $e) {
        Log::error('❌ Error processing CHIP callback: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => 'Callback processing failed'], 500);
    }

    return response()->json(['message' => 'Callback processed successfully', 'data' => $data]);
}

    /**
     * Optional: Verify callback authenticity (via secret or signature header)
     */
    protected function verifyRequest(Request $request): bool
    {
        $expectedToken = config('chipin.webhook_secret');
        if (! $expectedToken) {
            // Skip verification if no secret configured
            return true;
        }

        $signature = $request->header('X-CHIP-Signature') ?? '';
        return hash_equals($expectedToken, $signature);
    }
}
