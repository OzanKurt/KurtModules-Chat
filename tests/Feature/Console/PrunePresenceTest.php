<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\PresenceStatus;
use Kurt\Modules\Chat\Models\Presence;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->user = StubUser::create(['email' => 'user@example.com']);
});

it('marks stale presence rows as offline', function () {
    Presence::query()->create([
        'user_id' => $this->user->id,
        'status' => PresenceStatus::Online,
        'heartbeat_at' => now()->subSeconds(600),
    ]);

    $this->artisan('chat:prune-presence')->assertSuccessful();

    /** @var Presence $presence */
    $presence = Presence::query()->where('user_id', $this->user->id)->firstOrFail();
    expect($presence->status)->toBe(PresenceStatus::Offline);
});

it('leaves fresh heartbeats untouched', function () {
    Presence::query()->create([
        'user_id' => $this->user->id,
        'status' => PresenceStatus::Online,
        'heartbeat_at' => now()->subSeconds(10),
    ]);

    $this->artisan('chat:prune-presence')->assertSuccessful();

    /** @var Presence $presence */
    $presence = Presence::query()->where('user_id', $this->user->id)->firstOrFail();
    expect($presence->status)->toBe(PresenceStatus::Online);
});
