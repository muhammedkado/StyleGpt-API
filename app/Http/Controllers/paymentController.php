<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function checkTransaction(Request $request)
    {
        // Verify webhook signature
        $paddleSignature = $request->header('Paddle-Signature');
        if (!$paddleSignature) {
            Log::warning('Missing Paddle-Signature header');
            return response()->json(['error' => true, 'message' => 'Missing Paddle-Signature header'], 403);
        }

        // Extract timestamp and signature
        [$timestamp, $signature] = explode('.', $paddleSignature, 2);

        // Build the signed payload
        $signedPayload = $timestamp . '.' . $request->getContent();

        // Hash the signed payload
        $computedSignature = hash_hmac('sha1', $signedPayload, env('PADDLE_WEBHOOK_SECRET'));

        // Compare signatures
        if ($signature !== $computedSignature) {
            // Invalid signature
            Log::warning('Invalid Paddle webhook signature');
            return response()->json(['error' => true, 'message' => 'Invalid signature'], 403);
        }

        // Proceed with transaction processing
        $payload = $request->all();

        // Extract customer ID
        $customerId = data_get($payload, 'data.customer_id');
        if (!$customerId) {
            Log::warning('Missing customer_id in the payload');
            return response()->json(['error' => true, 'message' => 'Missing customer_id in the payload'], 400);
        }

        // Find user based on customer ID
        $user = User::where('customerid', $customerId)->first();
        if (!$user) {
            Log::warning('User not found');
            return response()->json(['error' => true, 'message' => 'User not found'], 404);
        }

        // Process user subscription and credit based on webhook data
        $user->issubscriptions = true;
        $basicValue = data_get($payload, 'data.items.0.price.custom_data');
        if ($basicValue) {
            foreach ($basicValue as $key => $value) {
                $key = str_replace(' ', '', $key);
                if (in_array($key, ['Basic', 'pro', 'pro_blus', 'yearBasic', 'yearPro', 'yearProBluse'])) {
                    $user->credit = intval($value);
                    break; // Assuming only one credit value should be applied
                }
            }
        }
        $user->save();

        return response()->json(['success' => true, 'message' => 'Transaction processed successfully']);
    }

}
