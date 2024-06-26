<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use BenBjurstrom\Replicate\Replicate;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;


class GenerateImageController extends Controller
{
    /**
     * Handles the creation of a new prediction.
     *
     * @param \Illuminate\Http\Request $request The incoming request object.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the result or error message.
     */
    public function create(request $request)
    {
        try {
            $uid = $request->request->get('uid');
            $image = $request->request->get('image');
            $prompt = $request->request->get('prompt');
            $aPrompt = $request->request->get('a_prompt');
            $nPrompt = $request->request->get('n_prompt');
            $resolution = $request->request->get('resolution');
            $detectResolution = $request->request->get('detect_resolution');
            $steps = $request->request->get('steps');
            $roomType = $request->request->get('roomType');
            $roomTheme = $request->request->get('roomTheme');
            $test = $request->request->get('test');
            DB::beginTransaction(); // Start a transaction

            $user = User::where('uid', $uid)->lockForUpdate()->first();
            // Check if the user exists
            if (!$user) {
                return response()->json(['error' => true, 'message' => 'User not found'], 404);
            }

            // Check if the user has sufficient credit
            if ($user->credit < 1) {
                return response()->json(['error' => true, 'message' => 'Credit is not enough'], 400);
            }
            $user->credit -= 1;
            $user->save();

            DB::commit(); // Commit the transaction
            // Prepare the request to the Replicate API
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
                ])
                ->post('https://api.replicate.com/v1/predictions', [
                    'version' => '854e8727697a057c525cdb45ab037f64ecca770a1769cc52287c2e56472a247b',
                    'input' => [
                        "eta" => 0,
                        "image" => $image ?? 'https://replicate.delivery/pbxt/IJZOELWrncBcjdE1s5Ko8ou35ZOxjNxDqMf0BhoRUAtv76u4/room.png',
                        "scale" => 11,
                        "prompt" => $prompt ?? "a " . $roomTheme . ' ' . $roomType,
                        "a_prompt" => $aPrompt ?? "best quality, extremely detailed, photo from Pinterest, interior, cinematic photo, ultra-detailed, ultra-realistic, award-winning, high-resolution photography interior design, photorealistic, camera shots, dramatic, realistic",
                        "n_prompt" => $nPrompt ?? "poorly drawn hands, missing limb, disfigured, cut off, ugly, grain, low res, deformed, blurry, bad anatomy, disfigured, poorly drawn face, mutation, mutated, floating limbs, disconnected limbs, disgusting, poorly drawn, mutilated, mangled, extra fingers, duplicate artifacts, missing arms, mutated hands, mutilated hands, cloned face, malformed, blurry top wall, blurry walls, extra limbs, weird colors, watermark, blur haze, bad art",
                        "ddim_steps" => $steps ?? 60,
                        "num_samples" => "1",
                        "value_threshold" => 0.01,
                        "image_resolution" => $resolution ?? "512",
                        "detect_resolution" => $detectResolution ?? 512,
                        "distance_threshold" => 0.01,
                    ],
                ]);

            $responseData = $response->json();

            if ($response->successful()) {
                return $this->getPhoto($responseData, $uid, $image, $roomTheme, $roomType, $test);
            } else {
                DB::rollBack(); // Rollback the transaction if the API response indicates failure
                return response()->json([
                    'status' => [
                        'message' => $responseData['title'],
                        'detail' => $responseData['detail'],
                        'error' => true
                    ],
                ], 400);
            }
        } catch (RequestException $e) {
            DB::rollBack();
            return response()->json([
                'status' => [
                    'message' => 'Error',
                    'detail' => $e->getMessage(),
                    'error' => true
                ],
            ], 500);
        }
    }

    /**
     * Handles the retrieval of the generated photo from the prediction.
     *
     * @param array $data The response data from the prediction API.
     * @param string $uid The user ID.
     * @param string $originalImage The original image URL.
     * @param string $theme The room theme.
     * @param string $type The room type.
     * @param string $test A test parameter.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the result or error message.
     */
    private function getPhoto($data, $uid, $originalImage, $theme, $type, $test)
    {
        if ($data !== null && isset($data['urls']['get'])) {
            $endpointUrl = $data['urls']['get'];
            $restoredImage = null;
            while ($restoredImage === null) {
                $response = Http::withOptions(['verify' => false])
                    ->withHeaders([
                        'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
                        'Content-Type' => 'application/json',
                    ])
                    ->get($endpointUrl);
                if ($response->successful()) {
                    $responseData = $response->json();
                    if ($responseData['status'] === 'succeeded') {
                        $restoredImage = $responseData['output'][1];
                        return $this->updateImage($restoredImage, $uid, $originalImage, $theme, $type, $test);
                    } elseif ($responseData['status'] === 'failed') {
                        DB::rollBack();
                        return response()->json([
                            'status' => [
                                'message' => 'Status error: status did not return succeeded',
                                'error' => true
                            ],
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'status' => [
                            'message' => $responseData['status'],
                            'detail' => $responseData['detail'],
                            'error' => true
                        ],
                    ], 400);
                }
            }
        } else {
            DB::rollBack();
            return response()->json([
                'status' => [
                    'message' => 'Data is null or URLs.get is not set',
                    'error' => true
                ],
            ], 400);
        }
    }

    /**
     * Updates the image by sending it to the Replicate API for further processing.
     *
     * @param string $image The URL of the image to be updated.
     * @param string $uid The user ID.
     * @param string $originalImage The original image URL.
     * @param string $theme The room theme.
     * @param string $type The room type.
     * @param bool $test A test parameter (default is false).
     * @return \Illuminate\Http\JsonResponse The JSON response containing the result or error message.
     */
    public function updateImage($image, $uid, $originalImage, $theme, $type, $test = false)
    {
        try {
            // Check if this is a test request
            if ($test) {
                return $this->storeImageFromURL($image, $uid, $originalImage, $theme, $type, $test);
            }
            // Send a POST request to the Replicate API for further image processing
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
                ])
                ->post('https://api.replicate.com/v1/predictions', [
                    'version' => '4ed6fcc4373156ea6c57b19530b67046e8fdecb5254b55db487ce1718f95b3a4',
                    'input' => [
                        "seed" => 1337,
                        "image" => $image ?? "https://replicate.delivery/pbxt/KaWIzouebpWmVyaUKjHTDUkq5g6lO6tQdd0439zzWu27ArHo/Room%20(4).png",
                        "prompt" => "masterpiece, best quality, highres, <lora:more_details:0.5> <lora:SDXLrender_v2.0:1>",
                        "dynamic" => 6,
                        "scheduler" => "DPM++ 3M SDE Karras",
                        "creativity" => 0.35,
                        "resemblance" => 0.6,
                        "scale_factor" => 2,
                        "negative_prompt" => "(worst quality, low quality, normal quality:2) JuggernautNegative-neg",
                        "num_inference_steps" => 18,
                    ],
                ]);
            $responseData = $response->json();
            return $this->secondRequest($responseData, $uid, $originalImage, $theme, $type, $test);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => [
                    'message' => $e->getMessage(),
                    'error' => true
                ],
            ], 500);
        }
    }

    /**
     * Handles the second request to retrieve the final processed image from the prediction.
     *
     * @param array $data The response data from the initial prediction API request.
     * @param string $uid The user ID.
     * @param string $images The original image URL.
     * @param string $theme The room theme.
     * @param string $type The room type.
     * @param bool $test A test parameter.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the result or error message.
     */
    private function secondRequest($data, $uid, $images, $theme, $type, $test)
    {
        try {
            if ($data !== null && isset($data['urls']['get'])) {
                $endpointUrl = $data['urls']['get'];
                $restoredImage = null;
                // Loop until the restored image URL is retrieved or an error occurs
                while ($restoredImage === null) {
                    $response = Http::withOptions(['verify' => false])
                        ->withHeaders([
                            'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
                            'Content-Type' => 'application/json',
                        ])
                        ->get($endpointUrl);
                    if ($response->successful()) {
                        $responseData = $response->json();
                        if ($responseData['status'] === 'succeeded') {
                            $restoredImage = $responseData['output'];
                            return $this->storeImageFromURL($restoredImage, $uid, $images, $theme, $type, $test);
                        } elseif ($responseData['status'] === 'failed') {
                            return response()->json([
                                'status' => [
                                    'error' => true,
                                    'message' => 'Failed to process the request.',
                                    'details' => [
                                        'model' => 'philz1337x',
                                        'status' => 'failed',
                                        'reason' => 'The model encountered an error while processing the request.'
                                    ]
                                ],
                            ], 400);
                        }
                    }
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => [
                        'message' => 'Data is null or URLs.get is not set',
                        'error' => true
                    ],
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => [
                    'message' => $e->getMessage(),
                    'error' => true
                ],
            ], 500);
        }
    }

    /**
     * Stores the image from the provided URL to Google Cloud Storage and updates the database.
     *
     * @param string $imageUrl The URL of the image to be stored.
     * @param string $uid The user ID.
     * @param string $images The original image URL.
     * @param string $theme The room theme.
     * @param string $type The room type.
     * @param bool $test A test parameter.
     * @return \Illuminate\Http\JsonResponse The JSON response containing the result or error message.
     */
    public function storeImageFromURL($imageUrl, $uid, $images, $theme, $type, $test)
    {
        try {
            // Initialize Google Cloud Storage client
            $storage = new StorageClient([
                'keyFile' => json_decode(file_get_contents(storage_path('roomai-af76d-firebase-adminsdk-b4un1-19d796851a.json')), true)
            ]);

            // Get the default bucket
            $bucket = $storage->bucket('roomai-af76d.appspot.com');

            // Download image from the provided URL
            $imageData = $test ? file_get_contents($imageUrl) : file_get_contents($imageUrl[0]);

            if ($imageData === false) {
                return response()->json([
                    'status' => [
                        'message' => 'Failed to download image data from URL: ' . $imageUrl,
                        'error' => true
                    ],
                ], 500);
            }
            // Generate a uuid
            $uuid = Str::uuid();
            $url = $uid . '_' . $uuid;

            // Upload the image data to Firebase Storage
            $object = $bucket->upload($imageData, [
                'name' => 'rooms/' . $url,
                'metadata' => [
                    'contentType' => 'image/jpeg'
                ]
            ]);

            // Construct the URL to access the stored image
            $ImageUrll = "https://firebasestorage.googleapis.com/v0/b/roomai-af76d.appspot.com/o/rooms%2F" . $url . "?alt=media";
            $image = new Image();
            $user = User::where('uid', $uid)->first();
            $image->uid = $uid;
            $image->before = $images;
            $image->after = $ImageUrll;
            $image->theme = $theme;
            $image->type = $type;
            $image->save();
            return response()->json([
                'success' => true,
                'id' => $image->id,
                'image' => $ImageUrll,
                'theme' => $theme,
                'type' => $type,
                'credit' => $user->credit
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Handle the error gracefully
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
