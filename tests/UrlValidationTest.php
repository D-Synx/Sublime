<?php

declare(strict_types=1);

namespace Sublime\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sublime\HtmlElement;

final class UrlValidationTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function dangerousUrls(): iterable
    {
        foreach (['href', 'src', 'action', 'formaction', 'HREF'] as $name) {
            foreach (['javascript:alert(1)', 'vbscript:msgbox(1)', 'data:text/html,<script>x</script>'] as $scheme) {
                foreach (['plain' => $scheme, 'uppercase' => strtoupper($scheme), 'leading controls' => "\x01 \t\n" . $scheme, 'internal newline' => substr($scheme, 0, 2) . "\n" . substr($scheme, 2)] as $variant => $url) {
                    yield $name . ' ' . $scheme . ' ' . $variant => [$name, $url];
                }
            }
        }
    }

    #[DataProvider('dangerousUrls')]
    public function testRejectsDangerousUrlsAtConstruction(string $name, string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HtmlElement('a', [$name => $url]);
    }

    /** @return iterable<string, array{string}> */
    public static function safeUrls(): iterable
    {
        foreach (['https://example.test/?a=1&b=2', '/page', '#section', 'mailto:hello@example.test', 'tel:+33123456789', 'data:image/png;base64,AA==', '/javascript:example'] as $url) {
            yield $url => [$url];
        }
    }

    #[DataProvider('safeUrls')]
    public function testPreservesAndEscapesSafeUrls(string $url): void
    {
        self::assertSame('<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8') . '"></a>', (new HtmlElement('a', ['href' => $url]))->render());
    }
}
