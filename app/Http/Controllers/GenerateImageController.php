<?php

namespace App\Http\Controllers;
use App\Models\Image;
use App\Models\User;
use GuzzleHttp\Client AS Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use BenBjurstrom\Replicate\Replicate;
use GuzzleHttp\Exception\RequestException;
use Spatie\Async\Pool;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;

class GenerateImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return 'the index is ok ';
    }

    /**
     * Show the form for creating a new resource.
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
            $user = User::where('uid', $uid)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
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
                        "prompt" => $prompt ?? "a" . $roomTheme . $roomType,
                        "a_prompt" => $aPrompt ?? "best quality, extremely detailed, photo from Pinterest, interior, cinematic photo, ultra-detailed, ultra-realistic, award-winning, high-resolution photography interior design, photorealistic, camera shots, dramatic, realistic",
                        "n_prompt" => $nPrompt ?? "poorly drawn hands, missing limb, disfigured, cut off, ugly, grain, low res, deformed, blurry, bad anatomy, disfigured, poorly drawn face, mutation, mutated, floating limbs, disconnected limbs, disgusting, poorly drawn, mutilated, mangled, extra fingers, duplicate artifacts, missing arms, mutated hands, mutilated hands, cloned face, malformed, blurry top wall, blurry walls, extra limbs, weird colors, watermark, blur haze, bad art",
                        "ddim_steps" => $steps ?? 60,
                        "num_samples" => "1",
                        "value_threshold" => 0.01,
                        "image_resolution" => $resolution ?? "768",
                        "detect_resolution" => $detectResolution ?? 512,
                        "distance_threshold" => 0.01,
                    ],
                ]);
            $responseData = $response->json();

            if ($response->successful()) {
                return $this->getPhoto($responseData, $uid, $image);
            } else {
                return response()->json([
                    'status'=>[
                        'message' => $responseData['title'],
                        'detail' => $responseData['detail'],
                        'error' => true
                    ],
                ]);
            }

        } catch (RequestException $e) {
            // Handle Guzzle HTTP exceptions
            return response()->json([
                'status'=>[
                    'message' => 'Error',
                    'detail' => $e->getMessage(),
                    'error' => true
                ],
            ]);
        }
    }

    private function getPhoto($data, $uid)
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
                        $image = new Image();
                        $image->uid = $uid;
                        $image->before = $responseData['input']['image']; // Set your 'before' value here
                        $image->after = $restoredImage;
                        $image->save();
                        return response()->json([
                            'status'=>[
                                'message' => $responseData['status'],
                                'error' => false
                            ],
                            'image' => $restoredImage
                        ]);
                    } elseif ($responseData['status'] === 'failed') {
                        return response()->json([
                            'status'=>[
                                'message' => 'status error is don\'t return succeeded',
                                'error' => true
                            ],
                        ]);
                    }
                } else {
                    usleep(1000000);
                }
            }
        } else {
            return response()->json([
                'status'=>[
                    'message' => 'Data is null or URLs.get is not set',
                    'error' => true
                ],
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
