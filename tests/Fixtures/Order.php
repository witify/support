<?php

namespace Witify\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Witify\Support\Model\IsResource;
use Witify\Support\Model\IsResourceTrait;
use Witify\Support\Model\SanitizesHtmlTrait;

/**
 * @property int $id
 * @property string $number
 * @property string|null $notes
 * @property string|null $client_name
 * @property Carbon|null $shipped_at
 */
class Order extends Model implements IsResource
{
    use IsResourceTrait;
    use SanitizesHtmlTrait;

    protected $guarded = [];

    /**
     * @return array<int, string>
     */
    public function sanitizeHtmlFields(): array
    {
        return ['notes'];
    }

    public function newEloquentBuilder($query): OrderQueryBuilder
    {
        return new OrderQueryBuilder($query);
    }

    public function getResourceTitle(): string
    {
        return $this->number;
    }

    public function getResourceSubtitle(): ?string
    {
        return $this->client_name;
    }

    public static function getResourceIcon(): string
    {
        return 'heroicons:shopping-bag-16-solid';
    }

    public static function getResourceColor(): string
    {
        return 'blue';
    }

    public function getResourceAdminTo(): string
    {
        return 'orders/' . $this->id;
    }
}
