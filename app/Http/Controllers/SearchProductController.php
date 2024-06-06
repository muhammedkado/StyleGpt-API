<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class SearchProductController extends Controller
{
    /**
     * Handles the search product request.
     *
     * @param \Illuminate\Http\Request $request The incoming request object.
     * @return \Illuminate\Http\JsonResponse The JSON response containing search results or error message.
     */
    public function searchProduct(Request $request)
    {
        $apiKey = env('google_search_API_KEY');
        $client = new Client([
            'base_uri' => 'https://serpapi.com',
        ]);

        // Define the query parameters for the SerpAPI request
        $query = [
            "engine" => "google_lens",
            "url" => $request->input('image_url'),
            "no_cache" => true, // Ensure fresh results by bypassing cache
        ];

        try {
            $response = $client->get('/search', [
                'query' => array_merge(['serp_api_key' => $apiKey], $query),
            ]);

            // Decode the JSON response from the API
            $result = json_decode($response->getBody()->getContents(), true);
            $filteredMatches = [];

            // Filter the result and add only products that have a price
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
