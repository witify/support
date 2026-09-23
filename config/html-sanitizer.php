<?php

/*
 * Allow-list used by Witify\Support\Html\RichTextSanitizer to sanitize rich text
 * fields on save (see Witify\Support\Model\SanitizesHtmlTrait).
 *
 * The list mirrors what sprintify-ui's BaseRichText (TipTap) can produce.
 * Extend it when enabling new editor features — never with elements or
 * attributes that can execute script (script, iframe, on* handlers).
 */
return [
    // element => allowed attributes
    'allowed_elements' => [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'sub' => [],
        // data-* attributes are used by tiptap-footnotes references
        'sup' => ['data-footnote-reference', 'data-id'],
        // Highlight (multicolor) renders <mark data-color style="background-color: …">
        'mark' => ['data-color', 'style'],
        'a' => ['href', 'target', 'rel', 'class'],
        'ul' => [],
        'ol' => ['data-footnotes', 'class'],
        'li' => ['id', 'class'],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'pre' => [],
        'code' => [],
        'hr' => [],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ],

    // Benign containers: the tag is removed but its children are kept,
    // so legacy or pasted content loses wrappers instead of text.
    'blocked_elements' => [
        'div',
        'span',
        'section',
        'article',
        'font',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
    ],

    'link_schemes' => ['http', 'https', 'mailto', 'tel'],

    // Footnote anchors are fragment links (#fn-…); relative URLs cannot
    // carry a javascript: scheme so this is safe to allow.
    'allow_relative_links' => true,

    // data: is required because BaseRichText inlines images as base64.
    'media_schemes' => ['http', 'https', 'data'],

    // Uploaded images are served from the app itself (/storage/…).
    'allow_relative_medias' => true,

    'force_attributes' => [
        'a' => ['rel' => 'noopener noreferrer'],
    ],

    // Symfony's default (20 000 chars) silently truncates long documents.
    'max_input_length' => 1_000_000,
];
