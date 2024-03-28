<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
class UserController extends Controller
{
    public function create(Request $request)
    {
        try {
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    'unique:users,email',
                ],
                'uid' => [
                    'required',
                    'string',
                    Rule::unique('users', 'uid'),
                ],
                'name' => [
                    'required',
                    'string',
                ],
            ], [
                'email.required' => 'The email address is required.',
                'email.email' => 'The email address must be a valid email format.',
                'email.unique' => 'The email address is already in use.',
                'uid.required' => 'The UID is required.',
                'uid.string' => 'The UID must be a string.',
                'uid.unique' => 'The UID is already in use.',
            ]);
            $name = $request->input('name');
            $email = $request->input('email');
            $uid = $request->input('uid');
            $image = $request->input('image');
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('PADDLE_API_KEY'),
                    "Content-Type" => "application/json",
                ])
                ->post('https://sandbox-api.paddle.com/customers', [
                    "name" => $name,
                    "email" => $email
                ]);

            $responseData = $response->json();
            if (empty($responseData['error'])) {
                $user = new User();
                $user->name = $name;
                $user->email = $email;
                $user->uid = $uid;
                $user->image = $image;
                $user->customerid = $responseData['data']['id'];
                $user->save();
                return response()->json(['success' => true, 'message' => 'User created successfully']);
            } else {
                return response()->json([
                    'status' => [
                        'message' =>$responseData['error']['code'],
                        'detail' => $responseData['error']['detail'],
                        'error' => true
                    ],
                ], 400);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }
    }

    public function getUserByUid($uid)
    {
        try {
            // Find the user by UID
            $user = User::where('uid', $uid)->first();

            if (!$user) {
                // If user not found, return an error response
                return response()->json(['error' => true, 'message' => 'User not found'], 404);
            }

            return response()->json($user);
        } catch (\Exception $e) {
            // Log and return an error response
            \Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Failed to fetch user'], 500);
        }
    }

    public function getImageByUid($uid)
    {
        try {
            // Retrieve the user by UID
            $user = User::where('uid', $uid)->first();

            if (!$user) {
                // If user not found, return an error response
                return response()->json(['error' => true, 'message' => 'User not found'], 404);
            }
            $images = $user->images()->orderBy('created_at', 'desc')->get();
            // Format the response data
            $responseData = $images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'before' => $image->before,
                    'after' => $image->after,
                    'theme' => $image->theme,
                    'type' => $image->type,
                    'published' => $image->published,
                    'createdAt' => $image->created_at->format('Y-m-d H:i'),
                ];
            });

            // Return JSON response
            return response()->json($responseData);
        } catch (\Exception $e) {
            // Log and return an error response
            \Log::error('Error fetching images by UID: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' =>  $e->getMessage()], 500);
        }
    }
}
