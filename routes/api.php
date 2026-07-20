<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Chat\Http\Controllers\ConversationController;
use Kurt\Modules\Chat\Http\Controllers\MessageController;
use Kurt\Modules\Chat\Http\Controllers\ParticipantController;
use Kurt\Modules\Chat\Http\Controllers\ReactionController;
use Kurt\Modules\Chat\Http\Controllers\ReadStateController;

/*
|--------------------------------------------------------------------------
| Chat REST API
|--------------------------------------------------------------------------
|
| Registered by ChatServiceProvider::packageBooted() via the Core API kit's
| registerModuleApi(), which wraps this file in the module route group
| (prefix `api/chat`, base middleware + the `chat-api` throttle, name prefix
| `chat.api.`). This is the history + send + management surface that complements
| real-time broadcasting; it does NOT replace it — sending a message here still
| dispatches the domain event so websocket clients update.
|
| Chat has no public surface: every endpoint requires an authenticated,
| participating user. The whole group therefore carries the module auth
| middleware (guests get 401 on reads and writes alike). Participant scoping and
| write authorization are enforced per action through the chat Policies.
|
*/

Route::middleware(config('chat.http.auth_middleware', ['auth']))->group(function (): void {
    // Conversations / rooms
    Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('conversations/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

    // Message history + lifecycle
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::patch('messages/{message}', [MessageController::class, 'update'])->name('messages.update');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // Reactions
    Route::post('messages/{message}/reactions', [ReactionController::class, 'store'])->name('reactions.store');
    Route::delete('messages/{message}/reactions', [ReactionController::class, 'destroy'])->name('reactions.destroy');

    // Read state
    Route::post('conversations/{conversation}/read', [ReadStateController::class, 'store'])->name('read.store');
    Route::get('conversations/{conversation}/unread-count', [ReadStateController::class, 'count'])->name('read.count');

    // Participants
    Route::get('conversations/{conversation}/participants', [ParticipantController::class, 'index'])->name('participants.index');
    Route::post('conversations/{conversation}/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::delete('conversations/{conversation}/participants/{user}', [ParticipantController::class, 'destroy'])->name('participants.destroy');
});
