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
    $this->bob = StubUser::create(['email' => 'bob@example.com']);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);

    /** @var Participant $aliceParticipant */
    $aliceParticipant = $this->room->participants()->create([
        'user_id' => $this->alice->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => ParticipantNotifications::All->value,
    ]);
    /** @var Participant $bobParticipant */
    $bobParticipant = $this->room->participants()->create([
        'user_id' => $this->bob->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => ParticipantNotifications::All->value,
    ]);

    $this->aliceParticipant = $aliceParticipant;
    $this->bobParticipant = $bobParticipant;
});

it('auto-unarchives recipient participants when a new message arrives', function () {
    $this->bobParticipant->archive();
    expect($this->bobParticipant->fresh()->isArchived())->toBeTrue();

    $this->room->send($this->alice, 'hey bob, are you back?');

    expect($this->bobParticipant->fresh()->isArchived())->toBeFalse();
});

it('does not unarchive the sender themselves', function () {
    $this->aliceParticipant->archive();
    expect($this->aliceParticipant->fresh()->isArchived())->toBeTrue();

    $this->room->send($this->alice, 'thinking out loud');

    // Alice (the sender) stays archived; only recipients flip.
    expect($this->aliceParticipant->fresh()->isArchived())->toBeTrue();
});

it('respects the auto_unarchive_on_new_message config flag when disabled', function () {
    config()->set('chat.auto_unarchive_on_new_message', false);

    $this->bobParticipant->archive();

    $this->room->send($this->alice, 'silent send');

    expect($this->bobParticipant->fresh()->isArchived())->toBeTrue();
});
