<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;

/**
 * @mixin Reaction
 */
final class ReactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_id' => $this->reactable_id,
            'user_id' => $this->user_id,
            'emoji' => $this->emoji,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
