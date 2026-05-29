<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Participant;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $convo = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    /** @var Participant $participant */
    $participant = $convo->participants()->create([
        'user_id' => $this->alice->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => ParticipantNotifications::All->value,
    ]);
    $this->participant = $participant;
});

it('archives and unarchives a participant', function () {
    expect($this->participant->isArchived())->toBeFalse();

    $this->participant->archive();

    expect($this->participant->fresh()->isArchived())->toBeTrue();
    expect($this->participant->fresh()->archived_at)->not->toBeNull();

    $this->participant->unarchive();

    expect($this->participant->fresh()->isArchived())->toBeFalse();
    expect($this->participant->fresh()->archived_at)->toBeNull();
});

it('exposes archived and notArchived scopes', function () {
    $bob = StubUser::create(['email' => 'bob@example.com']);

    /** @var Participant $bobParticipant */
    $bobParticipant = $this->participant->conversation->participants()->create([
        'user_id' => $bob->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => ParticipantNotifications::All->value,
    ]);

    $bobParticipant->archive();

    expect(Participant::query()->archived()->count())->toBe(1);
    expect(Participant::query()->notArchived()->count())->toBe(1);
});

it('persists settings as an array', function () {
    $this->participant->settings = ['theme' => 'dark', 'pinned' => true];
    $this->participant->save();

    $fresh = $this->participant->fresh();

    expect($fresh->settings)->toBe(['theme' => 'dark', 'pinned' => true]);
});
