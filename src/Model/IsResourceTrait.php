<?php

namespace Witify\Support\Model;

use Illuminate\Support\Str;

trait IsResourceTrait
{
    public function initializeIsResourceTrait(): void
    {
        $this->append('resource_data');
    }

    public function getResourceAdminUrl(): string
    {
        $to = $this->getResourceAdminTo();

        Str::startsWith($to, '/') ?: $to = '/' . $to;

        return url(rtrim((string) config('support.admin_url', '/admin'), '/') . $to);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getResourceSharedData(): array
    {
        return [
            'icon' => static::getResourceIcon(),
            'color' => static::getResourceColor(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getResourceDataAttribute(): array
    {
        return [
            'title' => $this->getResourceTitle(),
            'subtitle' => $this->getResourceSubtitle(),
            'icon' => $this->getResourceIcon(),
            'color' => $this->getResourceColor(),
            'admin_to' => $this->getResourceAdminTo(),
            'admin_url' => $this->getResourceAdminUrl(),
            'model_type' => self::class,
        ];
    }
}
