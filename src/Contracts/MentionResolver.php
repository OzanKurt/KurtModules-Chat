<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Contracts;

interface MentionResolver
{
    /**
     * Return an array of user keys (int|string) that should be marked as mentioned
     * for the given message body.
     *
     * @return array<int, int|string>
     */
    public function resolve(string $body): array;
}
