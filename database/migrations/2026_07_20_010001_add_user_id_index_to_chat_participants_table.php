<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_participants', function (Blueprint $table): void {
            // The existing unique(['conversation_id', 'user_id']) index cannot
            // serve user_id-leading lookups ("my conversations", the unread
            // fan-out grouped by participant), so add a standalone index on
            // user_id for those access paths.
            $table->index('user_id', 'chat_participants_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('chat_participants', function (Blueprint $table): void {
            $table->dropIndex('chat_participants_user_id_index');
        });
    }
};
