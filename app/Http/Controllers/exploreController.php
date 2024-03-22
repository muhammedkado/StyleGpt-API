<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image; // Import the Image model

class exploreController extends Controller
{
    public function index()
    {
        // Select 20 random images
        $randomImages = Image::where('published', true)->inRandomOrder()->limit(20)->get();

        // Prepare the response data array
        $responseData = [];

        // Iterate through each random image
        foreach ($randomImages as $image) {
            // Add image data to the response data array
            $responseData[] = [
                'before' => $image->before,
                'after' => $image->after,
                'theme' => $image->theme,
                'type' => $image->type,
                'createdAt' => $image->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Return the response as JSON
        return response()->json($responseData);
    }

    public function publish(Request $request)
    {
        try {
            $id = $request->input('id');
            $publish = $request->input('published');

            $image = Image::find($id);
            if (!$image) {
                return response()->json(['error' => 'Image not found'], 404);
            }
            $image->published = $publish;
            $image->save();
            return response()->json(['success' => true, 'message' => 'Image has been published successfully'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
