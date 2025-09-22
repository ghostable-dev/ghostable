<?php

namespace App\Messaging\Mail\Broadcast;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Blog\Models\Post;
use App\Messaging\Mail\UnsubscribableMail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PostPublishedMailable extends UnsubscribableMail
{
    public function __construct(
        protected User|MailingListEmail $recipient,
        protected Post $post
    ) {
        parent::__construct($recipient);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->post->title,
            to: $this->recipient->email
        );
    }

    protected function getContent(): Content
    {
        return new Content(
            view: 'mail.broadcast.post-published',
            with: [
                'post' => $this->post,
            ],
        );
    }
}
