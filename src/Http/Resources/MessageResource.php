<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Chat\Models\Message;

/**
 * @mixin Message
 */
final class MessageResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'type' => $this->type->value,
            'body' => $this->body,
            'data' => $this->data,
            'edited_at' => optional($this->edited_at)->toIso8601String(),
            'reactions' => ReactionResource::collection($this->whenLoaded('reactions')),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
