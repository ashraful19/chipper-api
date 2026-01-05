<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\CreateFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites;
        return FavoriteResource::collection($favorites);
    }

    public function store(CreateFavoriteRequest $request, ?Post $post = null, ?User $user = null)
    {
        $favoritable = $post ?? $user;
        
        $favorite = $favoritable->favoritedBy()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        if (!$favorite->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Already favorited'
            ], Response::HTTP_CONFLICT);
        }

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, ?Post $post = null, ?User $user = null)
    {
        $favoritable = $post ?? $user;
        
        $favoritable->favoritedBy()->where('user_id', $request->user()->id)->firstOrFail()->delete();

        return response()->noContent();
    }
}
