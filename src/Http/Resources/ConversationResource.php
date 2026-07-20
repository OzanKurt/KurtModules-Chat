<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Chat\Models\Conversation;

/**
 * @mixin Conversation
 */
final class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'visibility' => $this->visibility->value,
            'created_by' => $this->created_by,
            'last_message_at' => optional($this->last_message_at)->toIso8601String(),
            'participants_count' => $this->whenCounted('participants'),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
