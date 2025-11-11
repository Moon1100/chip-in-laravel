<?php

return [
    'api_key'   => env('CHIPIN_API_KEY', ''),
    'brand_id'  => env('CHIPIN_BRAND_ID', ''),
    'mode'      => env('CHIPIN_MODE', 'sandbox'), // sandbox or production
    'base_url'  => env('CHIPIN_BASE_URL', 'https://gate.chip-in.asia/api/v1/'),
    'callback_handler' => env('CHIP_IN_CALLBACKHANDLER', \App\Http\Controllers\PaymentController::class),

];
