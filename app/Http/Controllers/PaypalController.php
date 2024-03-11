<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    public function paypal(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        $paypalToken = $provider->getAccessToken();

        $respone = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('success'),
                "cancel_url" => route('cancel')
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $request->price
                    ]
                ]
            ]
        ]);

        if(isset($respone['id']) && $respone['id'] != null){
            foreach($respone['links'] as $link){
                if($link['rel'] === 'approve'){
                    return response()->json(['approval_url' => $link['href']], 200);
                }
            }
        }

        return response()->json(['error' => 'Failed to create PayPal order'], 500);
    }

    public function success(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        $paypalToken = $provider->getAccessToken();

        $respone = $provider->capturePaymentOrder($request->token);

        if(isset($respone['status']) && $respone['status'] == 'COMPLETED'){


            return response()->json(['message' => 'Payment completed successfully'], 200);
        }else{
            return response()->json(['error' => 'Payment failed'], 500);
        }
    }

    public function cancel()
    {
        return response()->json(['message' => 'Payment has been canceled'], 200);
    }
}
