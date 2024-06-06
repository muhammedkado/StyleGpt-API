<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;

class exploresController extends Controller
{
    /**
     * Retrieves 20 random published and explored images for exploration.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing random image data.
     */
    public function explore()
    {
        // Select 20 random images that are published and marked for exploration
        $randomImages = Image::where('published', true)->where('explore', true)->inRandomOrder()->limit(20)->get();

        // Prepare the response data array
        $responseData = [];

        // Iterate through each random image
        foreach ($randomImages as $image) {
            // Add image data to the response data array
            $responseData[] = [
                'success' => true,
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

    /**
     * Changes the publish status of an image.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing image ID and publish status.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating success or failure of the operation.
     */
    public function publish(Request $request)
    {
        try {
            $id = $request->input('id');
            $publish = $request->input('published');

            $image = Image::find($id);
            if (!$image) {
                return response()->json(['error' => true, 'message' => 'Image not found'], 404);
            }
            $image->published = $publish;
            if ($publish) {
                $image->publish_at = now();
            }
            $image->save();
            return response()->json(['success' => true, 'message' => 'publish status has been changing successfully'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => true, 'message'  => 'Internal server error'], 500);
        }
    }

    /**
     * Retrieves published images ordered by publish timestamp for administrative exploration.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing image data for administrative exploration.
     */
    public function adminExplore()
    {
        // Select published images, ordered by publish timestamp descending
        $randomImages = Image::where('published', true)
            ->orderByDesc('publish_at')
            ->get();

        // Prepare the response data array
        $responseData = [];

        // Iterate through each random image
        foreach ($randomImages as $image) {
            // Add image data to the response data array
            $responseData[] = [
                'id' => $image->id,
                'before' => $image->before,
                'after' => $image->after,
                'theme' => $image->theme,
                'type' => $image->type,
                'explore' => $image->explore,
                'createdAt' => $image->created_at->format('Y-m-d H:i:s'),
                'publishAt' => $image->publish_at,
            ];
        }

        // Return the response as JSON
        return response()->json($responseData);
    }

    /**
     * Changes the explore status of an image for administrative purposes.
     *
     * @param \Illuminate\Http\Request $request The incoming request containing image ID and explore status.
     * @return \Illuminate\Http\JsonResponse The JSON response indicating success or failure of the operation.
     */
    public function adminPublish(Request $request)
    {
        try {
            $id = $request->input('id');
            $explore = $request->input('published');
            if (empty($id)) {
                return response()->json(['error' => true, 'message' => 'Image ID can\'t be empty'], 404);
            }

            if (empty($explore)) {
                return response()->json(['error' => true, 'message' => 'published parameter can\'t be empty'], 404);
            }

            $image = Image::find($id);
            if (!$image) {
                return response()->json(['error' => true, 'message' => 'Image not found'], 404);
            }
            // Update the explore status of the image
            $image->explore = $explore;
            $image->save();
            return response()->json(['success' => true, 'message' => 'explore status has been changing successfully'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => true, 'message' => 'Internal server error', 'detail' => $e->getMessage()], 500);
        }
    }
}
