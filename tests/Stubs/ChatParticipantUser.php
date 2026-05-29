<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Tests\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Chat\Concerns\IsChatParticipant;
use Kurt\Modules\Chat\Contracts\ChatParticipant;

final class ChatParticipantUser extends Model implements Authenticatable, ChatParticipant
{
    use IsChatParticipant;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }
}
