<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('registers no API routes in the default headless mode', function (): void {
    // The base TestCase leaves chat.http.mode at its config default
    // (env('CHAT_HTTP_MODE', 'headless')), so the module is safe-by-default and
    // must not register any of its REST routes.
    expect(config('chat.http.mode'))->toBe('headless');

    expect(Route::has('chat.api.conversations.index'))->toBeFalse();
    expect(Route::has('chat.api.messages.store'))->toBeFalse();

    $this->getJson('/api/chat/conversations')->assertNotFound();
});
