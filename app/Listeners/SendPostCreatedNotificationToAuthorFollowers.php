<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Models\User;
use App\Notifications\PostCreated as PostCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendPostCreatedNotificationToAuthorFollowers implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PostCreated $event): void
    {
        $post = $event->post;
        $authorUser = $post->user;

        $followers = $authorUser->favoritedBy->map(fn($favorite) => $favorite->user)->filter();

        // followers here will always be a user instance
        // Because only users can favorite a user. no other type of model can favorite a user.
        // But still keeping this check for future proofing.
        $followers = $followers->whereInstanceOf(User::class);

        Notification::send($followers, new PostCreatedNotification($post));
    }
}
