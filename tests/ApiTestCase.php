<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Tests;

use Illuminate\Foundation\Application;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

/**
 * Test case for the REST API suite: boots the module with `chat.http.mode=api`
 * so the routes register, and points the Core user resolver + auth guard at the
 * StubUser model. The mode must be set in defineEnvironment (before providers
 * boot) because routes are registered during packageBooted().
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('chat.http.mode', 'api');

        // Resolve module user references and the auth guard against StubUser.
        $app['config']->set('kurtmodules.user_model', StubUser::class);
        $app['config']->set('auth.providers.users.model', StubUser::class);
    }
}
