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
use Kurt\Modules\Chat\Observers\MessageObserver;
use Kurt\Modules\Chat\Policies\ConversationPolicy;
use Kurt\Modules\Chat\Policies\MessagePolicy;
use Kurt\Modules\Chat\Policies\ReactionPolicy;
use Kurt\Modules\Core\Modules\ModuleManifest;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
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
            ->discoversMigrations()
            ->hasCommands([
                PrunePresenceCommand::class,
                DemoCommand::class,
            ]);
    }

    protected function moduleManifest(): ?ModuleManifest
    {
        return ModuleManifest::make('chat')
            ->name('Chat')
            ->description('Real-time chat for Laravel: rooms, DMs, threads, presence, reactions, attachments, mentions.');
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        $this->registerModuleApi(__DIR__.'/../../routes/api.php');

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
