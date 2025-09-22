<?php

namespace App\Messaging\Campaigns\Broadcast;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Blog\Models\Post;
use App\Core\Enums\NotificationCategory;
use App\Messaging\Contracts\Campaign;
use App\Messaging\Mail\Broadcast\PostPublishedMailable;
use Illuminate\Contracts\Mail\Mailable;

class PostPublished extends BroadcastCampaign
{
    protected const PREFIX = 'broadcast.post.';

    public function __construct(protected Post $post) {}

    public function key(): string
    {
        return self::makeKey((string) $this->post->id);
    }

    public static function makeKey(string $id): string
    {
        return static::PREFIX.$id;
    }

    public static function supportsKey(string $key): bool
    {
        return str_starts_with($key, self::PREFIX);
    }

    public static function fromKey(string $key): Campaign
    {
        $id = substr($key, strlen(self::PREFIX));

        $post = Post::findOrFail($id);

        return new static($post);
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new PostPublishedMailable(recipient: $user, post: $this->post);
    }

    public function categories(): array
    {
        return [NotificationCategory::BLOG];
    }
}
