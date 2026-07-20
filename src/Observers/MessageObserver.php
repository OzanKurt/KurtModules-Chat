<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Observers;

use InvalidArgumentException;
use Kurt\Modules\Chat\Events\MentionFired;
use Kurt\Modules\Chat\Events\MessageDeleted;
use Kurt\Modules\Chat\Events\MessageEdited;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Support\MentionExtractor;
use Kurt\Modules\Interactions\Mentions\Models\Mention;

final class MessageObserver
{
    public function __construct(private readonly MentionExtractor $extractor) {}

    public function creating(Message $message): void
    {
        $max = (int) config('chat.message_max_length', 4000);
        if ($max > 0 && mb_strlen($message->body ?? '') > $max) {
            throw new InvalidArgumentException(sprintf(
                'Chat message body exceeds the maximum length of %d characters.',
                $max,
            ));
        }
    }

    public function saving(Message $message): void
    {
        // Only extract mentions when the body actually changes (or on create).
        if ($message->exists && ! $message->isDirty('body')) {
            return;
        }

        $userIds = $this->extractor->extract($message->body ?? '');

        // Always store as the public array property; observer uses it post-create.
        $message->pendingMentionUserIds = $userIds;
    }

    public function created(Message $message): void
    {
        $userIds = array_values(array_unique($message->pendingMentionUserIds));

        foreach ($userIds as $userId) {
            /** @var Mention $mention */
            $mention = $message->mentions()->firstOrCreate([
                'mentioned_user_id' => $userId,
            ]);

            MentionFired::dispatch($mention);
        }

        // Reset transient state.
        $message->pendingMentionUserIds = [];
    }

    public function updated(Message $message): void
    {
        if ($message->wasChanged('body')) {
            // Re-sync mention rows for the new body.
            $userIds = array_values(array_unique($message->pendingMentionUserIds));

            foreach ($userIds as $userId) {
                /** @var Mention $mention */
                $mention = $message->mentions()->firstOrCreate([
                    'mentioned_user_id' => $userId,
                ]);

                MentionFired::dispatch($mention);
            }

            $message->pendingMentionUserIds = [];

            MessageEdited::dispatch($message);
        }
    }

    public function deleted(Message $message): void
    {
        MessageDeleted::dispatch(
            (int) $message->getKey(),
            (int) $message->conversation_id,
            $message->conversation->type,
        );
    }
}
