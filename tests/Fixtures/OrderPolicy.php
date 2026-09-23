<?php

namespace Witify\Support\Tests\Fixtures;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->first_name !== 'Guest';
    }

    public function view(User $user, Order $order): bool
    {
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->first_name === 'Ada';
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
