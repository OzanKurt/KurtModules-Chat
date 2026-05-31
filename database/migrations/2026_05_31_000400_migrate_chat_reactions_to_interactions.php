<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kurt\Modules\Chat\Models\Message;

/**
 * Moves chat reactions onto the shared Interactions store. Each `chat_reactions`
 * row becomes an `interactions_reactions` row reacting on the Message
 * (reactable_type = Message::class), then the legacy table is dropped.
 *
 * Guarded on both tables so the migration is a no-op when either side is absent
 * (e.g. the Interactions package not yet migrated, or an install that never had
 * chat reactions).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_reactions') || ! Schema::hasTable('interactions_reactions')) {
            return;
        }

        DB::table('chat_reactions')->orderBy('id')->chunkById(500, function ($rows): void {
            $now = now();
            $payload = [];

            foreach ($rows as $row) {
                $payload[] = [
                    'user_id' => $row->user_id,
                    'reactable_type' => Message::class,
                    'reactable_id' => $row->message_id,
                    'emoji' => $row->emoji,
                    'custom_emoji_id' => null,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ];
            }

            if ($payload !== []) {
                DB::table('interactions_reactions')->insert($payload);
            }
        });

        Schema::dropIfExists('chat_reactions');
    }

    public function down(): void
    {
        // One-way data migration; the legacy chat_reactions table is not restored.
    }
};
