<?php

namespace App\Http\Controllers;
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
            $image = $request->request->get('image');
            $prompt = $request->request->get('prompt');
            $a_prompt = $request->request->get('a_prompt');
            $n_prompt = $request->request->get('n_prompt');
            $resolution = $request->request->get('resolution') ?? '256';
            $detect_resolution = $request->request->get('detect_resolution') ?? 256;
            $steps = $request->request->get('steps');

            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Token ' . env('REPLICATE_API_TOKEN'),
                ])
                ->post('https://api.replicate.com/v1/predictions', [
                    'version' => '854e8727697a057c525cdb45ab037f64ecca770a1769cc52287c2e56472a247b',
                    'input' => [
                        "eta" => 0,
                        "image" => $image ?? 'https://replicate.delivery/pbxt/IJZOELWrncBcjdE1s5Ko8ou35ZOxjNxDqMf0BhoRUAtv76u4/room.png',
                        "scale" => 9,
                        "prompt" => $prompt ?? "a room for gaming with gaming computers, gaming consoles, and gaming chairs",
                        "a_prompt" => $a_prompt ?? "best quality, extremely detailed, photo from Pinterest, interior, cinematic photo, ultra-detailed, ultra-realistic, award-winning",
                        "n_prompt" => $n_prompt ?? "longbody, lowres, bad anatomy, bad hands, missing fingers, extra digit, fewer digits, cropped, worst quality, low quality, walls changing, windows changing",
                        "ddim_steps" => $steps ?? 20,
                        "num_samples" => "1",
                        "value_threshold" => 0.1,
                        "image_resolution" => $resolution ?? "256",
                        "detect_resolution" => $detect_resolution ?? 256,
                        "distance_threshold" => 0.1,
                    ],
                ]);
            $responseData = $response->json();
            if ($response->successful()) {
                return $this->getPhoto($responseData);
            } else {
                return response()->json([
                    'status'=>[
                        'message' => $responseData['title'],
                        'detail' => $responseData['detail'],
                        'error' => true
                    ],
                    'image' => []
                ]);
            }

        } catch (RequestException $e) {
            // Handle Guzzle HTTP exceptions
            return $e->getMessage();
        }
    }

    private function getPhoto($data)
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
                        return response()->json([
                            'status'=>[
                                'message' => $responseData['status'],
                                'error' => false
                            ],
                            'image' => $restoredImage
                        ]);
                    } elseif ($responseData['status'] === 'failed') {
                        break;
                    } else {
                        usleep(1000000);
                    }
                } else {
                    return response()->json([
                        'status'=>[
                            'message' => $response['title'],
                            'detail' => $response['detail'],
                            'error' => true
                        ],
                        'image' => []
                    ]);
                }
            }
        } else {
            return 'Data is null or URLs.get is not set';
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
