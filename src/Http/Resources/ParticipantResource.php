<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Chat\Models\Participant;

/**
 * @mixin Participant
 */
final class ParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'role' => $this->role->value,
            'notifications' => $this->notifications->value,
            'joined_at' => optional($this->joined_at)->toIso8601String(),
            'last_read_at' => optional($this->last_read_at)->toIso8601String(),
            'archived_at' => optional($this->archived_at)->toIso8601String(),
        ];
    }
}
