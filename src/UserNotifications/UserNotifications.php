<?php

namespace Witify\Support\UserNotifications;

use Exception;

class UserNotifications
{
    /**
     * @var UserNotification[]
     */
    private array $notifications = [];

    public function push(string $key, UserNotification $userNotification): void
    {
        if (array_key_exists($key, $this->notifications)) {
            throw new Exception("Notification key already exists: {$key}");
        }

        $this->notifications[$key] = $userNotification;
    }

    /**
     * @return UserNotification[]
     */
    public function all(): array
    {
        return $this->notifications;
    }
}
