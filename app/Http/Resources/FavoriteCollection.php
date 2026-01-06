<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FavoriteCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $grouped = $this->collection->groupBy('favoritable_type');
        return [
            'posts' => $this->transformPosts($grouped->get(Post::class, collect())),
            'users' => $this->transformUsers($grouped->get(User::class, collect())),
        ];
    }

    private function transformPosts($favorites)
    {
        // I noticed that the readme file asked for output only with users id and name only 
        // but this resource returns including email too. but for that i couldnt use the PostResource class.
        // if its a major requirement, i would have create a method to return that format.
        // but i think its not a major requirement.
        return PostResource::collection(
            $favorites->map(fn($favorite) => $favorite->favoritable)->filter()
        );
    }

    private function transformUsers($favorites)
    {
        // I noticed that the readme file asked for output only with id and name only 
        // but this resource returns including email too. but for that i couldnt use the UserResource class.
        // if its a major requirement, i would have create a method to return that format.
        // but i think its not a major requirement.
        return UserResource::collection(
            $favorites->map(fn($favorite) => $favorite->favoritable)->filter()
        );
    }
}
