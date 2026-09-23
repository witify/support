<?php

namespace Witify\Support\Controller;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Witify\Support\Exception\RateLimitException;
use Witify\Support\Http\Confirmation\Actions\ConfirmRequestAction;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected function user(): ?Authenticatable
    {
        return Auth::user();
    }

    protected function rateLimit(string $key, int $attempts, ?string $message = null): void
    {
        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            throw new RateLimitException($message);
        }

        RateLimiter::increment($key);
    }

    protected function confirmRequest(
        ?string $title = null,
        ?string $message = null,
        ?string $confirmText = null,
        ?string $cancelText = null,
        bool $requiresPassword = false
    ): bool|string {
        return (new ConfirmRequestAction(request()))
            ->setTitle($title)
            ->setMessage($message)
            ->setConfirmText($confirmText)
            ->setCancelText($cancelText)
            ->setRequiresPassword($requiresPassword)
            ->handle();
    }
}
