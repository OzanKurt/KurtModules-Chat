<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    config()->set('app.cipher', 'AES-256-CBC');

    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
});

it('round-trips plaintext when encryption is disabled', function () {
    config()->set('chat.encrypt_messages', false);

    $message = $this->room->send($this->alice, 'plain hello');

    expect($message->fresh()->body)->toBe('plain hello');

    $raw = DB::table('chat_messages')->where('id', $message->id)->value('body');
    expect($raw)->toBe('plain hello');
});

it('stores body encrypted at rest and decrypts on read when enabled', function () {
    config()->set('chat.encrypt_messages', true);

    $message = $this->room->send($this->alice, 'secret hello');

    // Accessor returns plaintext.
    expect(Message::query()->find($message->id)->body)->toBe('secret hello');

    // Raw column value differs from the plaintext (it's an encrypted Laravel payload).
    $raw = DB::table('chat_messages')->where('id', $message->id)->value('body');
    expect($raw)->not->toBe('secret hello');
    expect(Crypt::decryptString($raw))->toBe('secret hello');
});

it('falls back to raw value when decryption fails (legacy plaintext rows)', function () {
    config()->set('chat.encrypt_messages', false);

    // Write a plaintext row while encryption is OFF.
    $message = $this->room->send($this->alice, 'legacy plaintext');

    // Now turn encryption ON and read the legacy row — accessor should not throw.
    config()->set('chat.encrypt_messages', true);

    expect(Message::query()->find($message->id)->body)->toBe('legacy plaintext');
});
