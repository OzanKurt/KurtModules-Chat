<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kurt\Modules\Chat\Models\Message;

/**
 * Moves chat mentions onto the shared Interactions store. Each `chat_mentions`
 * row becomes an `interactions_mentions` row against the Message
 * (mentionable_type = Message::class), preserving the `seen_at` read receipt,
 * then the legacy table is dropped.
 *
 * Guarded on both tables so the migration is a no-op when either side is absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_mentions') || ! Schema::hasTable('interactions_mentions')) {
            return;
        }

        DB::table('chat_mentions')->orderBy('id')->chunkById(500, function ($rows): void {
            $now = now();
            $payload = [];

            foreach ($rows as $row) {
                $payload[] = [
                    'mentionable_type' => Message::class,
                    'mentionable_id' => $row->message_id,
                    'mentioned_user_id' => $row->user_id,
                    'seen_at' => $row->seen_at,
                    'created_at' => $row->created_at ?? $now,
                ];
            }

            if ($payload !== []) {
                DB::table('interactions_mentions')->insert($payload);
            }
        });

        Schema::dropIfExists('chat_mentions');
    }

    public function down(): void
    {
        // One-way data migration; the legacy chat_mentions table is not restored.
    }
};
