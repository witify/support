<?php

namespace Witify\Support\Exception;

use Exception;
use Illuminate\Http\JsonResponse;

class ReportableBusinessRuleException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], 400);
    }
}
