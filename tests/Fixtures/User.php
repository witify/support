<?php

namespace Witify\Support\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];
}
