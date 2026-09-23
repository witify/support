<?php

namespace Witify\Support\Tests;

use Witify\Support\Html\RichTextSanitizer;
use Witify\Support\Tests\Fixtures\Order;

class SanitizesHtmlTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        RichTextSanitizer::flushCache();

        parent::tearDown();
    }

    public function test_it_strips_script_capable_markup_on_save_and_keeps_the_allowed_elements(): void
    {
        $order = Order::query()->create([
            'number' => 'ORD-1',
            'notes' => '<p onclick="steal()">Hello <strong>world</strong></p><script>alert(1)</script><a href="javascript:alert(1)">x</a>',
        ]);

        $this->assertSame('<p>Hello <strong>world</strong></p><a rel="noopener noreferrer">x</a>', $order->fresh()->notes);
    }

    public function test_it_keeps_placeholder_urls_and_sanitizes_json_fields_per_locale(): void
    {
        $this->assertSame(
            '<p><a href=":url" rel="noopener noreferrer">Open</a></p>',
            RichTextSanitizer::clean('<p><a href=":url" onmouseover="x()">Open</a></p>')
        );

        $this->assertSame(
            ['en' => '<p>Hi</p>', 'fr' => '<p>Salut</p>'],
            RichTextSanitizer::clean(['en' => '<p>Hi<script>1</script></p>', 'fr' => '<div><p>Salut</p></div>'])
        );

        $this->assertSame('plain text', RichTextSanitizer::clean('plain text'));
        $this->assertNull(RichTextSanitizer::clean(null));
    }

    public function test_the_allow_list_comes_from_the_published_config(): void
    {
        config()->set('html-sanitizer.allowed_elements', ['p' => []]);
        config()->set('html-sanitizer.blocked_elements', ['strong']);
        RichTextSanitizer::flushCache();

        // A blocked element loses its tag and keeps its text; an unknown one is dropped with its text.
        $this->assertSame('<p>Hello world</p>', RichTextSanitizer::clean('<p>Hello <strong>world</strong><em>!</em></p>'));
    }
}
