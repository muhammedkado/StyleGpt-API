<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier\WebhookController as PaddleWebhookController;

class paymentController extends Controller
{
    private function customerList($email)
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . env('PADDLE_API_KEY'),
                "Content-Type" => "application/json",
            ])
            ->get('https://sandbox-api.paddle.com/customers', [
                'email' => $email
            ]);
        $responseData = $response->json();
        return $responseData['data'][0]['id'];
    }


   public function createTransaction(request $request)
   {
       $email = $request->request->get('email');
       $priceId = $request->request->get('priceId');

       $customerId = $this->customerList($email);

       $response = Http::withOptions(['verify' => false])
           ->withHeaders([
               'Authorization' => 'Bearer ' . env('PADDLE_API_KEY'),
               'Content-Type' => 'application/json',
           ])
           ->post('https://sandbox-api.paddle.com/transactions', [
               'items' => [
                   [
                       'quantity' => 1,
                       'price_id' => $priceId
                   ],
               ],
               'customer_id' => $customerId,
               'collection_mode' => 'automatic'
           ]);
       $response->json();
       return response()->json([
           'success' => true,
           'transactionsId' => $response['data']['id'],
       ]);

   }

    public function checkTransaction(request $request)
    {
        // Verify webhook signature
        $signatureValid = $request->hasValidSignature();
        if (!$signatureValid) {
            return response()->json(['error' => true, 'message' => 'Invalid webhook signature'], 403);
        }
        $payload = $request->all();
        $customerId = $payload['data']['customer_id'];
        $user = User::where('customerid', $customerId)->first();

        $user->issubscriptions = true;
        $basicValue = $payload['data']['items'][0]['price']['custom_data'];
        foreach ($basicValue as $key => $value) {
            $key = str_replace(' ', '', $key);
            if (in_array($key, ['Basic', 'pro', 'pro_blus', 'yearBasic', 'yearPro', 'yearProBluse'])) {
                $user->credit = intval($value);
                break; // Assuming only one credit value should be applied
            }
        }
        $user->save();

        return response()->json(['success' => true, 'message' => 'Transaction processed successfully']);
    }
}
