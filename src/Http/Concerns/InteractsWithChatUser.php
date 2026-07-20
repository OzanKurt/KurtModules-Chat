<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Http\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Resolves the authenticated request user as an Eloquent {@see Model}.
 *
 * The chat domain methods (`Conversation::send()`, `reactWith()`,
 * `unreadCountFor()`, …) type-hint `Model`, whereas `Request::user()` returns
 * the broader `Authenticatable`. These routes always sit behind the module auth
 * middleware, so the user is guaranteed present and (being the configured user
 * model) an Eloquent model; the assertion narrows the type for static analysis.
 */
trait InteractsWithChatUser
{
    protected function chatUser(Request $request): Model
    {
        $user = $request->user();

        assert($user instanceof Model);

        return $user;
    }
}
