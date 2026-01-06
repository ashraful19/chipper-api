<?php

namespace Tests\Feature;

use App\Events\PostCreated;
use App\Listeners\SendPostCreatedNotificationToAuthorFollowers;
use Illuminate\Support\Arr;
use App\Models\User;
use App\Notifications\PostCreated as PostCreatedNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_create_a_post()
    {
        $response = $this->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_user_can_create_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'body',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post',
                    'body' => 'This is a test post.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);
    }

    public function test_a_user_can_update_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'body' => 'Updated body.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Updated title',
            'body' => 'Updated body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_not_update_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $response = $this->actingAs($john)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($jack)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'title' => 'Original title',
            'body' => 'Original body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_destroy_one_of_his_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->deleteJson(route('posts.destroy', ['post' => $id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', [
            'id' => $id,
        ]);
    }

    public function test_post_created_event_is_dispatched_when_post_is_created()
    {
        Event::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $createdPostId = Arr::get($response->json(), 'data.id');

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
            'id' => $createdPostId,
        ]);

        Event::assertDispatched(PostCreated::class, fn($event) => $event->post->id === $createdPostId && $event->post->user_id === $user->id);
    }

    public function test_send_post_created_notification_listener_is_registered()
    {
        Event::fake();

        Event::assertListening(PostCreated::class, SendPostCreatedNotificationToAuthorFollowers::class);
    }

    public function test_followers_receive_post_created_notification()
    {
        Notification::fake();

        $postAuthor = User::factory()->create();
        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();

        $postAuthor->favoritedBy()->create([
            'user_id' => $follower1->id,
        ]);

        $postAuthor->favoritedBy()->create([
            'user_id' => $follower2->id,
        ]);

        $response = $this->actingAs($postAuthor)->postJson(route('posts.store'), [
            'title' => 'New post',
            'body'  => 'Post body',
        ]);
    
        $response->assertCreated();

        Notification::assertSentTo(
            [$follower1, $follower2],
            PostCreatedNotification::class
        );
    }

    public function test_no_notifications_sent_when_author_has_no_followers()
    {
        Notification::fake();

        $postAuthor = User::factory()->create();

        $response = $this->actingAs($postAuthor)->postJson(route('posts.store'), [
            'title' => 'New post',
            'body'  => 'Post body',
        ]);
        
        $response->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_a_user_can_create_a_post_with_image() 
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'New post',
            'body'  => 'Post body',
            'image' => UploadedFile::fake()->image('test.jpg')->size(1024),
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'body', 'image',
                ]
            ]);

        $imagePath = str_replace(url('/storage') . '/', '', $response->json('data.image'));

        $this->assertDatabaseHas('posts', [
            'title' => 'New post',
            'body'  => 'Post body',
            'image' => $imagePath,
        ]);

        Storage::disk('public')->assertExists($imagePath);
    }
}
