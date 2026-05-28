<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Support;

use Illuminate\Database\Eloquent\Model;

final class ConversationKey
{
    public static function forDirect(Model $a, Model $b): string
    {
        $ids = [(string) $a->getKey(), (string) $b->getKey()];
        sort($ids, SORT_NATURAL);

        return implode(':', $ids);
    }
}
