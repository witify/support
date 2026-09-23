<?php

namespace Witify\Support\Tests\Fixtures;

use Illuminate\Http\JsonResponse;
use Witify\Support\Controller\Controller;

class OrderController extends Controller
{
    public function destroy(): JsonResponse
    {
        $this->confirmRequest(title: 'Delete the order?', message: '<p>This cannot be undone.</p>');

        return new JsonResponse(['deleted' => true, 'user' => $this->user()?->getAuthIdentifier()]);
    }

    public function close(): JsonResponse
    {
        $password = $this->confirmRequest(requiresPassword: true);

        return new JsonResponse(['closed' => true, 'password' => $password]);
    }

    public function ping(): JsonResponse
    {
        $this->rateLimit('ping', 2);

        return new JsonResponse(['pong' => true]);
    }
}
