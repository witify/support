<?php

namespace Witify\Support\Html;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizes rich text HTML against the allow-list in config/html-sanitizer.php,
 * published by witify/support.
 *
 * Applied on save by Witify\Support\Model\SanitizesHtmlTrait so stored HTML is safe
 * to render (v-safe-html client side) no matter where the write came from.
 */
class RichTextSanitizer
{
    private const PLACEHOLDER_TOKEN_PREFIX = '/__placeholder__/';

    private static ?HtmlSanitizer $sanitizer = null;

    /**
     * Sanitize a rich text value. Arrays (translatable or JSON fields) are
     * cleaned recursively; non-strings and strings without markup pass
     * through untouched.
     */
    public static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(static fn ($item) => self::clean($item), $value);
        }

        if (! is_string($value) || ! str_contains($value, '<')) {
            return $value;
        }

        $placeholders = [];
        $value = self::protectUrlPlaceholders($value, $placeholders);

        return strtr(self::sanitizer()->sanitize($value), $placeholders);
    }

    /**
     * Herald placeholders used as a whole URL (`href=":url"`) are not valid
     * URLs and make the sanitizer throw. Swap them for a relative token during
     * sanitization and restore them afterwards.
     *
     * @param  array<string, string>  $placeholders
     */
    private static function protectUrlPlaceholders(string $value, array &$placeholders): string
    {
        return (string) preg_replace_callback(
            '/\b(href|src)="(:[A-Za-z_][A-Za-z0-9_.]*)"/',
            static function (array $matches) use (&$placeholders): string {
                $token = self::PLACEHOLDER_TOKEN_PREFIX . count($placeholders);
                $placeholders[$token] = $matches[2];

                return $matches[1] . '="' . $token . '"';
            },
            $value,
        );
    }

    /**
     * Reset the cached sanitizer, e.g. after changing config in a test.
     */
    public static function flushCache(): void
    {
        self::$sanitizer = null;
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(self::buildConfig());
    }

    private static function buildConfig(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->withMaxInputLength(config('html-sanitizer.max_input_length'));

        foreach (config('html-sanitizer.allowed_elements') as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        foreach (config('html-sanitizer.blocked_elements') as $element) {
            $config = $config->blockElement($element);
        }

        foreach (config('html-sanitizer.force_attributes') as $element => $attributes) {
            foreach ($attributes as $name => $value) {
                $config = $config->forceAttribute($element, $name, $value);
            }
        }

        return $config
            ->allowLinkSchemes(config('html-sanitizer.link_schemes'))
            ->allowMediaSchemes(config('html-sanitizer.media_schemes'))
            ->allowRelativeLinks(config('html-sanitizer.allow_relative_links'))
            ->allowRelativeMedias(config('html-sanitizer.allow_relative_medias'));
    }
}
