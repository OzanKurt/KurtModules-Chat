<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_presence', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained(config('auth.providers.users.table', 'users'))->cascadeOnDelete();
            $table->string('status')->default('offline');
            $table->string('status_message')->nullable();
            $table->timestamp('heartbeat_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_presence');
    }
};
