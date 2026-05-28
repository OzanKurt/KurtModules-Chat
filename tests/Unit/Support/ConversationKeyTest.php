<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Chat\Support\ConversationKey;

final class StubKeyUser extends Model
{
    protected $table = 'stub_users';

    public $timestamps = false;

    public function getKey(): mixed
    {
        return $this->attributes['id'] ?? null;
    }
}

it('returns the same key regardless of argument order', function () {
    $a = new StubKeyUser(['id' => 7]);
    $b = new StubKeyUser(['id' => 12]);

    $ab = ConversationKey::forDirect($a, $b);
    $ba = ConversationKey::forDirect($b, $a);

    expect($ab)->toBe($ba);
});

it('produces different keys for different pairs', function () {
    $a = new StubKeyUser(['id' => 1]);
    $b = new StubKeyUser(['id' => 2]);
    $c = new StubKeyUser(['id' => 3]);

    expect(ConversationKey::forDirect($a, $b))
        ->not->toBe(ConversationKey::forDirect($a, $c))
        ->and(ConversationKey::forDirect($a, $b))
        ->not->toBe(ConversationKey::forDirect($b, $c));
});

it('sorts numerically (natural sort)', function () {
    $a = new StubKeyUser(['id' => 100]);
    $b = new StubKeyUser(['id' => 21]);

    $key = ConversationKey::forDirect($a, $b);

    expect($key)->toBe('21:100');
});
