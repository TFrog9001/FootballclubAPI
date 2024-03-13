<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    public function paypal(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        $paypalToken = $provider->getAccessToken();

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.success'),
                "cancel_url" => route('paypal.cancel')
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->input('ticket.cost')
                    ]
                ]
            ]
        ]);

        if(isset($response['id']) && $response['id'] != null){
            return response()->json(['orderId' => $response['id']], 200);
        }

        return response()->json(['error' => 'Failed to create PayPal order'], 500);
    }

    public function success(Request $request)
    {
        // Xử lý thanh toán thành công ở đây
        // Ví dụ: trả về thông báo thành công
        return response()->json(['message' => 'Payment completed successfully'], 200);
    }

    public function cancel()
    {
        // Xử lý khi thanh toán bị hủy
        // Ví dụ: trả về thông báo hủy bỏ
        return response()->json(['message' => 'Payment has been canceled'], 200);
    }
}
