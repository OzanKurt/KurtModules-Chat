<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Kurt\Modules\Chat\Enums\PresenceStatus;
use Kurt\Modules\Chat\Models\Presence;

final class PrunePresenceCommand extends Command
{
    protected $signature = 'chat:prune-presence';

    protected $description = 'Mark chat_presence rows whose heartbeat is older than config(chat.presence.offline_after_seconds) as offline.';

    public function handle(): int
    {
        $seconds = (int) config('chat.presence.offline_after_seconds', 90);

        $threshold = Carbon::now()->subSeconds($seconds);

        $updated = Presence::query()
            ->where('heartbeat_at', '<', $threshold)
            ->where('status', '!=', PresenceStatus::Offline->value)
            ->update(['status' => PresenceStatus::Offline->value]);

        $this->info("Marked {$updated} presence row(s) as offline.");

        return self::SUCCESS;
    }
}
