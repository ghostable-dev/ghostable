<?php

namespace App\Messaging\Commands;

use App\Messaging\Jobs\SendMessage;
use App\Messaging\Models\Message;
use Illuminate\Console\Command;

class SendQueuedMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messaging:send-queued';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $messages = Message::where('status', 'queued')->get();

        foreach ($messages as $message) {
            SendMessage::dispatch($message);
        }
    }
}
