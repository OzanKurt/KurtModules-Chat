<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Core\Contracts\UserResolver;

final class DemoCommand extends Command
{
    protected $signature = 'chat:demo';

    protected $description = 'Seed a demo chat room + DM with sample messages.';

    public function handle(UserResolver $users): int
    {
        $userTable = $users->table();
        $primaryKey = $users->primaryKey();

        /** @var array<int, int|string> $userIds */
        $userIds = DB::table($userTable)
            ->orderBy($primaryKey)
            ->take(2)
            ->pluck($primaryKey)
            ->all();

        if (count($userIds) < 2) {
            $this->error('Need at least 2 users to seed chat demo data.');

            return self::FAILURE;
        }

        $modelClass = $users->modelClass();
        /** @var Model $a */
        $a = $modelClass::query()->whereKey($userIds[0])->firstOrFail();
        /** @var Model $b */
        $b = $modelClass::query()->whereKey($userIds[1])->firstOrFail();

        /** @var Conversation $room */
        $room = Conversation::query()->create([
            'type' => ConversationType::Room,
            'name' => 'General',
            'description' => 'Demo room.',
            'visibility' => ConversationVisibility::Public,
            'created_by' => $a->getKey(),
        ]);

        $now = now();
        $room->participants()->createMany([
            ['user_id' => $a->getKey(), 'role' => 'owner', 'joined_at' => $now, 'notifications' => 'all'],
            ['user_id' => $b->getKey(), 'role' => 'member', 'joined_at' => $now, 'notifications' => 'all'],
        ]);

        $room->send($a, 'Welcome to the demo room!');
        $room->send($b, 'Hello!');

        $dm = Conversation::directBetween($a, $b);
        $dm->send($a, 'Hey, this is a direct message.');
        $dm->send($b, 'And this is a reply.');

        $this->info('Demo data seeded (1 room + 1 DM).');

        return self::SUCCESS;
    }
}
