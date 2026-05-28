<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Enums;

enum ConversationVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
