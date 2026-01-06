<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('posts.favorites.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_guest_can_not_favorite_a_user()
    {
        $user = User::factory()->create();

        $this->postJson(route('users.favorites.store', ['user' => $user]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('posts.favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_favorite_a_user()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('users.favorites.store', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $userToFavorite->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_favorite_himself()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('users.favorites.store', ['user' => $user]))
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'favoritable' => [
                        'You cannot favorite yourself.'
                    ]
                ]
            ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('posts.favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('posts.favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_user_from_his_favorites()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('users.favorites.store', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $userToFavorite->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('users.favorites.destroy', ['user' => $userToFavorite]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $userToFavorite->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('posts.favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    public function test_a_user_can_not_remove_a_non_favorited_user()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('users.favorites.destroy', ['user' => $userToFavorite]))
            ->assertNotFound();
    }

    public function test_a_user_can_view_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $favoritedUser = User::factory()->create();

        $post->favoritedBy()->create([
            'user_id' => $user->id,
        ]);

        $favoritedUser->favoritedBy()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'posts' => [
                        '*' => ['id', 'title', 'body', 'user' => ['id', 'name']]
                    ],
                    'users' => [
                        '*' => ['id', 'name']
                    ]
                ]
            ]);

        $this->assertCount(1, $response->json('data.posts'));
        $this->assertCount(1, $response->json('data.users'));
    }

    public function test_a_user_with_no_favorites_gets_empty_arrays()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk()
            ->assertJson([
                'data' => [
                    'posts' => [],
                    'users' => [],
                ]
            ]);
    }
}
