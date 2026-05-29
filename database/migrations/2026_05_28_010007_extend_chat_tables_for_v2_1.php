<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table): void {
            $table->json('data')->nullable()->after('description');
        });

        Schema::table('chat_participants', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('muted_until');
            $table->json('settings')->nullable()->after('notifications');
        });

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->string('type')->default('user')->after('parent_id');
            $table->json('data')->nullable()->after('body');
        });

        // Allow author-less system messages. Laravel 12+ supports SQLite column
        // modifications natively via temp-table rebuild, so a driver guard is no
        // longer required.
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                ->references('id')
                ->on(config('auth.providers.users.table', 'users'))
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Author-less system messages cannot survive a downgrade — drop them
        // before re-enforcing the NOT NULL constraint on user_id.
        DB::table('chat_messages')->whereNull('user_id')->delete();

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')
                ->references('id')
                ->on(config('auth.providers.users.table', 'users'))
                ->cascadeOnDelete();
        });

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropColumn(['type', 'data']);
        });

        Schema::table('chat_participants', function (Blueprint $table): void {
            $table->dropColumn(['archived_at', 'settings']);
        });

        Schema::table('chat_conversations', function (Blueprint $table): void {
            $table->dropColumn('data');
        });
    }
};
