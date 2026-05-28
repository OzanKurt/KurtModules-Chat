<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Support;

use Illuminate\Contracts\Container\Container;
use Kurt\Modules\Chat\Contracts\MentionResolver;

final class MentionExtractor
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array<int, int|string>
     */
    public function extract(string $body): array
    {
        $configured = config('chat.mentions.resolver');
        /** @var class-string<MentionResolver> $resolverClass */
        $resolverClass = is_string($configured) && $configured !== ''
            ? $configured
            : UsernameMentionResolver::class;

        /** @var MentionResolver $resolver */
        $resolver = $this->container->make($resolverClass);

        return $resolver->resolve($body);
    }
}
