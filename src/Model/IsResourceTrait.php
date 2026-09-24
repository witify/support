<?php

namespace Witify\Support\Model;

use Illuminate\Support\Str;

trait IsResourceTrait
{
    public function initializeIsResourceTrait(): void
    {
        $this->append('resource_data');
    }

    /**
     * The color of the model in the interface. A model overrides it when it has its own.
     */
    public static function getResourceColor(): string
    {
        return 'gray';
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
