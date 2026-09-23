<?php

namespace Witify\Support\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Witify\Support\Http\Confirmation\Actions\ConfirmRequestAction;

class ClearConfirmationFlow
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 428) {
            return $response;
        }

        $flow = $request->header('X-Confirmation-Flow');

        ConfirmRequestAction::clearFlow($request, is_string($flow) ? $flow : null);

        return $response;
    }
}
