<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Contracts\MentionResolver;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Events\MentionFired;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com', 'username' => 'alice']);
    $this->bob = StubUser::create(['email' => 'bob@example.com', 'username' => 'bob']);
    $this->carol = StubUser::create(['email' => 'carol@example.com', 'username' => 'carol']);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
});

it('extracts mentions by username and creates Mention rows', function () {
    Event::fake([MentionFired::class]);

    $message = $this->room->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'hello @bob and @carol — how are you?',
    ]);

    $mentions = $message->mentions()->pluck('user_id')->all();
    expect($mentions)->toContain($this->bob->id);
    expect($mentions)->toContain($this->carol->id);
    expect($mentions)->not->toContain($this->alice->id);

    Event::assertDispatchedTimes(MentionFired::class, 2);
});

it('ignores unknown usernames', function () {
    Event::fake([MentionFired::class]);

    $message = $this->room->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'hi @ghost',
    ]);

    expect($message->mentions()->count())->toBe(0);
    Event::assertNotDispatched(MentionFired::class);
});

it('supports a custom resolver via config swap', function () {
    Event::fake([MentionFired::class]);

    $carol = $this->carol;
    $resolver = new class($carol->id) implements MentionResolver
    {
        public function __construct(private readonly int $userId) {}

        /** @return array<int, int|string> */
        public function resolve(string $body): array
        {
            return [$this->userId];
        }
    };

    app()->instance($resolver::class, $resolver);
    config()->set('chat.mentions.resolver', $resolver::class);

    $message = $this->room->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'completely unrelated body',
    ]);

    $mentions = $message->mentions()->pluck('user_id')->all();
    expect($mentions)->toBe([$carol->id]);
    Event::assertDispatchedTimes(MentionFired::class, 1);
});
