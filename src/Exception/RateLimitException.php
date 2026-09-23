<?php

namespace Witify\Support\Exception;

use Exception;
use Illuminate\Http\JsonResponse;

class RateLimitException extends Exception implements ShouldntReportOnRequest
{
    public function __construct(?string $message = null)
    {
        if ($message === null) {
            $message = (string) __('support::messages.too_many_requests');
        }

        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], 429);
    }
}
