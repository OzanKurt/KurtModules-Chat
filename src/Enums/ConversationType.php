<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Enums;

enum ConversationType: string
{
    case Room = 'room';
    case Direct = 'direct';
}
