<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Enums;

enum ParticipantNotifications: string
{
    case All = 'all';
    case Mentions = 'mentions';
    case None = 'none';
}
