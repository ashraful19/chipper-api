<?php

namespace App\Http\Controllers;

use App\Events\PostCreated;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Requests\DestroyPostRequest;

/**
 * @group Posts
 *
 * API endpoints for managing posts
 */
class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->orderByDesc('created_at')->get();
        return PostResource::collection($posts);
    }

    public function store(CreatePostRequest $request)
    {
        $user = $request->user();

        $image = $request->file('image');
        $imagePath = null;
        if ($image) {
            $imagePath = $image->store('posts', 'public');
        }

        // Create a new post
        $post = Post::create([
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'user_id' => $user->id,
            'image' => $imagePath,
        ]);

        PostCreated::dispatch($post);

        return new PostResource($post);
    }

    public function show(Post $post)
    {
        return new PostResource($post);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->update([
            'title' => $request->input('title'),
            'body' => $request->input('body'),
        ]);

        return new PostResource($post);
    }

    public function destroy(DestroyPostRequest $request, Post $post)
    {
        $post->delete();

        return response()->noContent();
    }
}
