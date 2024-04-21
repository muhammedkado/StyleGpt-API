<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class SearchProductController extends Controller
{
    public function searchProduct(Request $request)
    {
        $apiKey = env('google_search_API_KEY');
        $client = new Client([
            'base_uri' => 'https://serpapi.com',
        ]);

        $query = [
            "engine" => "google_lens",
            "url" => $request->input('image_url'),
            "no_cache" => true,
        ];

        try {
            $response = $client->get('/search', [
                'query' => array_merge(['serp_api_key' => $apiKey], $query),
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            $filteredMatches = [];

            foreach ($result['visual_matches'] as $match) {
                if (isset($match['price'])) {
                    $filteredMatches[] = $match;
                }
            }

            return response()->json([
                'status' => $filteredMatches,
                'success' => true
            ], 200);
        } catch (ClientException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
