<?php

namespace App\Auth\Console\Commands;

use App\Auth\Models\CliLoginSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PruneCliLoginSessionsCommand extends Command
{
    protected $signature = 'cli-login:prune';

    protected $description = 'Remove expired CLI login sessions.';

    public function handle(): int
    {
        $expiredSessions = CliLoginSession::query()
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredSessions as $session) {
            Cache::forget($session->cacheKey());
            $session->delete();
            $count++;
        }

        $this->info(sprintf('Pruned %d expired CLI login session(s).', $count));

        return self::SUCCESS;
    }
}
