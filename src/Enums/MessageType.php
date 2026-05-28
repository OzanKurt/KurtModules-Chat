<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Enums;

enum MessageType: string
{
    case User = 'user';
    case System = 'system';
}
