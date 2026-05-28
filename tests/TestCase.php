<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Kurt\Modules\Chat\Providers\ChatServiceProvider;
use Kurt\Modules\Core\Providers\CoreServiceProvider;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends PackageTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function modulePackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
            ChatServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            MediaLibraryServiceProvider::class,
            ChatServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Disable broadcast route registration during tests so we don't need
        // an HTTP kernel or auth driver. Broadcasting is asserted via
        // Event::fake() in the tests that care.
        $app['config']->set('chat.broadcasting.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        // Add a `username` column on the users table for mention tests.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('email');
        });

        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
