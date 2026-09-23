<?php

namespace Witify\Support\Model;

use Witify\Support\Html\RichTextSanitizer;

/**
 * Sanitizes rich text attributes on every save so stored HTML is safe to
 * render regardless of which write path produced it.
 *
 * Declare the fields on the model:
 *
 *     use SanitizesHtmlTrait;
 *
 *     public function sanitizeHtmlFields(): array
 *     {
 *         return ['description'];
 *     }
 *
 * Works on plain string attributes, array/JSON casts and
 * spatie/laravel-translatable fields (both are JSON in the raw attributes,
 * so every locale gets sanitized).
 */
trait SanitizesHtmlTrait
{
    public static function bootSanitizesHtmlTrait(): void
    {
        static::saving(function (self $model): void {
            foreach ($model->sanitizeHtmlFields() as $field) {
                $model->sanitizeHtmlField($field);
            }
        });
    }

    /**
     * Attributes containing rich text HTML.
     *
     * @return array<int, string>
     */
    abstract public function sanitizeHtmlFields(): array;

    protected function sanitizeHtmlField(string $field): void
    {
        $raw = $this->attributes[$field] ?? null;

        if (! is_string($raw) || $raw === '') {
            return;
        }

        // Translatable and array-cast fields are stored as JSON strings.
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $this->attributes[$field] = json_encode(
                RichTextSanitizer::clean($decoded),
                JSON_UNESCAPED_UNICODE
            );

            return;
        }

        $this->attributes[$field] = RichTextSanitizer::clean($raw);
    }
}
