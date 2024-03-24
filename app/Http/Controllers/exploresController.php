<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image; // Import the Image model

class exploresController extends Controller
{
    public function explore()
    {
        // Select 20 random images
        $randomImages = Image::where('published', true)->where('explore', true)->inRandomOrder()->limit(20)->get();

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
            return response()->json(['success' => true, 'message' => 'publish status has been changing successfully'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function adminExplore()
    {
        // Select 20 random images
        $randomImages = Image::where('published', true)
            ->orderBy('created_at')
            ->get();
        // Prepare the response data array
        $responseData = [];

        // Iterate through each random image
        foreach ($randomImages as $image) {
            // Add image data to the response data array
            $responseData[] = [
                'imageId' => $image->id,
                'before' => $image->before,
                'after' => $image->after,
                'theme' => $image->theme,
                'type' => $image->type,
                'explore' => $image->explore,
                'createdAt' => $image->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Return the response as JSON
        return response()->json($responseData);
    }
    public function adminPublish(Request $request)
    {
        try {
            $id = $request->input('id');
            $explore = $request->input('published');
            if (empty($id)) {
                return response()->json(['error' => true, 'message' => 'Image ID can\'t be empty'], 404);
            }

            if (empty($explore)) {
                return response()->json(['error' => true, 'message' => 'explore can\'t be empty'], 404);
            }

            $image = Image::find($id);
            if (!$image) {
                return response()->json(['error' => 'Image not found'], 404);
            }
            $image->explore = $explore;
            $image->save();
            return response()->json(['success' => true, 'message' => 'explore status has been changing successfully'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => true, 'message' => 'Internal server error'], 500);
        }
    }
}
