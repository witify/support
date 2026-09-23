<?php

namespace Witify\Support;

use Closure;

/**
 * Values the package needs from the host application without reading its
 * models. The application registers resolvers in a service provider; the
 * config values are the fallback when no resolver is set.
 */
final class Host
{
    private static ?Closure $companyNameResolver = null;

    /**
     * @param  Closure(): (string|null)  $resolver
     */
    public static function resolveCompanyNameUsing(Closure $resolver): void
    {
        self::$companyNameResolver = $resolver;
    }

    /**
     * Name that signs the mails, e.g. the company of the application settings.
     */
    public static function companyName(): string
    {
        $resolved = self::$companyNameResolver === null ? null : (self::$companyNameResolver)();

        if (is_string($resolved) && $resolved !== '') {
            return $resolved;
        }

        $configured = config('support.company_name');

        return (string) (is_string($configured) && $configured !== '' ? $configured : config('app.name'));
    }

    public static function forgetResolvers(): void
    {
        self::$companyNameResolver = null;
    }
}
