<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Enums;

enum PresenceStatus: string
{
    case Online = 'online';
    case Away = 'away';
    case Dnd = 'dnd';
    case Offline = 'offline';
}
