<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Broadcast;
use Kurt\Modules\Chat\Console\Commands\DemoCommand;
use Kurt\Modules\Chat\Console\Commands\PrunePresenceCommand;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Models\Reaction;
use Kurt\Modules\Chat\Observers\MessageObserver;
use Kurt\Modules\Chat\Policies\ConversationPolicy;
use Kurt\Modules\Chat\Policies\MessagePolicy;
use Kurt\Modules\Chat\Policies\ReactionPolicy;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

final class ChatServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'chat';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-chat')
            ->hasConfigFile('chat')
            ->hasTranslations()
            ->hasMigrations([
                'create_chat_conversations_table',
                'create_chat_participants_table',
                'create_chat_messages_table',
                'create_chat_reactions_table',
                'create_chat_mentions_table',
                'create_chat_presence_table',
                'extend_chat_tables_for_v2_1',
            ])
            ->hasCommands([
                PrunePresenceCommand::class,
                DemoCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        Message::observe(MessageObserver::class);

        if ((bool) config('chat.broadcasting.enabled', true)) {
            Broadcast::routes();
            require __DIR__.'/../../routes/channels.php';
        }

        if ($this->app->runningInConsole()) {
            $this->app->booted(function (): void {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('chat:prune-presence')->everyMinute();
            });
        }

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Conversation::class, ConversationPolicy::class);
        $gate->policy(Message::class, MessagePolicy::class);
        $gate->policy(Reaction::class, ReactionPolicy::class);
    }
}
