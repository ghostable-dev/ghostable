<?php

namespace App\Messaging\Mail;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Headers;

abstract class UnsubscribableMail extends Mailable
{
    protected string $unsubscribeLink;
    
    public function __construct(protected User|MailingListEmail $recipient)
    {
        $this->unsubscribeLink = $this->recipient->unsubscribeLink();
    }
    
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => "<{$this->unsubscribeLink}>",
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    public function content(): Content
    {
        $content = $this->getContent();

        $content->with('unsubscribable', true);
        $content->with('unsubscribe_url', $this->unsubscribeLink);
        
        return $content;
    }
    
    abstract protected function getContent(): Content;
}
