<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Chat\Contracts\MentionResolver;
use Kurt\Modules\Core\Contracts\UserResolver;

final class UsernameMentionResolver implements MentionResolver
{
    public function __construct(
        private readonly UserResolver $users,
        private readonly Repository $config,
    ) {}

    /**
     * @return array<int, int|string>
     */
    public function resolve(string $body): array
    {
        $pattern = (string) $this->config->get('chat.mentions.pattern');
        $column = (string) $this->config->get('chat.mentions.username_column', 'username');

        if ($pattern === '' || preg_match_all($pattern, $body, $matches) === 0) {
            return [];
        }

        /** @var array<int, string> $usernames */
        $usernames = array_values(array_unique($matches[1] ?? []));
        if ($usernames === []) {
            return [];
        }

        /** @var array<int, int|string> $ids */
        $ids = DB::table($this->users->table())
            ->whereIn($column, $usernames)
            ->pluck($this->users->primaryKey())
            ->all();

        return $ids;
    }
}
