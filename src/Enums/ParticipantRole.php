<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Enums;

enum ParticipantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
}
