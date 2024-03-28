<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
       $transactionId = $request->query('transaction_id');
       $uid = $request->query('uid');
       $response = Http::withOptions(['verify' => false])
           ->withHeaders([
               'Authorization' => 'Bearer ' . env('PADDLE_API_KEY'),
               'Content-Type' => 'application/json',
           ])
           ->get('https://sandbox-api.paddle.com/transactions/' . $transactionId);

        $user = User::where('uid', $uid)->first();
       // return $user;
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'User not found'], 404);
        }

        $subscriptionsCheck = $user->issubscriptions;
        $responseData = json_decode($response, true);
        if ($response['data']['status'] === 'completed' && $subscriptionsCheck === false) {
            $user->issubscriptions = true;
            $basicValue = $responseData['data']['items'][0]['price']['custom_data'];
           foreach ($basicValue as $key => $value) {
               $key = str_replace(' ', '', $key);
               if ($key === 'Basic') {
                   $user->credit = intval($value);
                   //return $user->credit;
               } elseif ($key === 'pro') {
                   $user->credit = intval($value);
               } elseif ($key === 'pro_blus') {
                   $user->credit = $value;
               } elseif ($key === 'yearBasic') {
                   $user->credit = $value;
               } elseif ($key === 'yearPro') {
                   $user->credit = $value;
               } elseif ($key === 'yearProBluse') {
                   $user->credit = $value;
               }
            }
            $user->save();
        } else {
            return response()->json([
                'error' => true,
                'subscriptionsCheck' => $subscriptionsCheck,
                'status' => $response['data']['status']
            ], 404);
        }
    }
}
