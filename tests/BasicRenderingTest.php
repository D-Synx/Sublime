<?php

declare(strict_types=1);

namespace Sublime\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function Sublime\body_;
use function Sublime\div_;
use function Sublime\h1_;
use function Sublime\p_;
use function Sublime\raw_html;
use function Sublime\Sublime;

final class BasicRenderingTest extends TestCase
{
    public function testCanRenderDirectlyWithoutCallback(): void
    {
        $html = Sublime(body_(data: div_(class: 'app', data: p_('Hello'))));
        self::assertSame('<body><div class="app"><p>Hello</p></div></body>', $html);
    }

    public function testCanUseShortNamedHtmlModeWithoutCallback(): void
    {
        $html = Sublime(class: 'html', data: body_(data: div_(class: 'app', data: p_('Hello'))));
        self::assertSame('<body><div class="app"><p>Hello</p></div></body>', $html);
    }

    public function testCanPutContentBeforeModeOption(): void
    {
        self::assertSame('<p>Hello</p>', Sublime(p_('Hello'), class: 'html'));
    }

    public function testRawHtmlCanRenderDirectly(): void
    {
        self::assertSame('<strong>Trusted & raw</strong>', Sublime(raw_html('<strong>Trusted & raw</strong>')));
    }

    public function testNullRendersEmptyString(): void
    {
        self::assertSame('', Sublime(null));
    }

    public function testUnknownModeIsRejectedBeforeCallbackRuns(): void
    {
        $called = false;
        try {
            Sublime(function () use (&$called): void {
                $called = true;
            }, class: 'unknown');
            self::fail('Unsupported mode should throw.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('unknown', $exception->getMessage());
            self::assertFalse($called);
        }
    }

    public function testExistingZeroParameterCallbackStillWorks(): void
    {
        $html = Sublime(fn () => body_(data: [
            div_(class: 'wrapper', data: [
                h1_('Hello'),
                p_('World'),
            ]),
        ]));

        self::assertSame('<body><div class="wrapper"><h1>Hello</h1><p>World</p></div></body>', $html);
    }
}
