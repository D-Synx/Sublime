<?php

declare(strict_types=1);

namespace Sublime\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sublime\HtmlElement;
use Sublime\TagFactory;

use function Sublime\p_;
use function Sublime\raw_html;
use function Sublime\Sublime;
use function Sublime\sublime_;

function namedFactoryCallback(TagFactory $tags): HtmlElement
{
    return $tags->p('Hello');
}

function namedZeroCallback(): HtmlElement
{
    return p_('Hello');
}

final class SublimeCallbackTest extends TestCase
{
    /** @return iterable<string, array{callable}> */
    public static function callbacks(): iterable
    {
        yield 'zero closure' => [fn () => p_('Hello')];
        yield 'typed closure' => [fn (TagFactory $tags) => $tags->p('Hello')];
        yield 'untyped closure' => [fn ($tags) => $tags->p('Hello')];
        yield 'nullable typed' => [fn (?TagFactory $tags) => $tags?->p('Hello')];
        yield 'named typed' => [__NAMESPACE__ . '\namedFactoryCallback'];
        yield 'named zero' => [__NAMESPACE__ . '\namedZeroCallback'];
        yield 'static string' => [CallbackFixture::class . '::render'];
        yield 'static array' => [[CallbackFixture::class, 'render']];
        yield 'instance array' => [[new CallbackFixture(), 'instanceRender']];
        yield 'invokable' => [new CallbackFixture()];
    }

    #[DataProvider('callbacks')]
    public function testSupportedCallbackForms(callable $callback): void
    {
        self::assertSame('<p>Hello</p>', Sublime($callback));
        self::assertSame('<p>Hello</p>', Sublime($callback, class: 'html'));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSignatures(): iterable
    {
        foreach (['two', 'scalar', 'object', 'mixed', 'union', 'variadic', 'reference'] as $signature) {
            yield $signature => [$signature];
        }
    }

    #[DataProvider('invalidSignatures')]
    public function testRejectsSignatureBeforeInvokingCallback(string $signature): void
    {
        $called = false;
        $record = function () use (&$called): void {
            $called = true;
        };
        $callback = match ($signature) {
            'two' => function ($first, $second = null) use ($record): void { $record(); },
            'scalar' => function (string $tags) use ($record): void { $record(); },
            'object' => function (object $tags) use ($record): void { $record(); },
            'mixed' => function (mixed $tags) use ($record): void { $record(); },
            'union' => function (TagFactory|string $tags) use ($record): void { $record(); },
            'variadic' => function (...$tags) use ($record): void { $record(); },
            'reference' => function (&$tags) use ($record): void { $record(); },
            default => throw new \LogicException('Unknown callback test signature.'),
        };
        try {
            Sublime($callback);
            self::fail('Invalid callback signature should throw.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('callback', $exception->getMessage());
            self::assertFalse($called);
        }
    }

    public function testCallbackResultsUseChildNormalization(): void
    {
        self::assertSame('&lt;1<p>Hello</p><b>&</b>', Sublime(fn () => ['<', true, false, null, [p_('Hello'), raw_html('<b>&</b>')]]));
        self::assertSame('', Sublime(fn () => null));
        self::assertSame('42', Sublime(fn () => 42));
        self::assertSame('ab', Sublime(function (): \Generator {
            yield 'a';
            yield 'b';
        }));
    }

    public function testUnsupportedCallbackResultIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sublime(fn () => new \stdClass());
    }

    public function testDistinctLegacyAliasRemainsCompatible(): void
    {
        self::assertSame('<p>Hello</p>', sublime_(fn () => p_('Hello')));
        self::assertSame('<p>Hello</p>', sublime_(fn (TagFactory $tags) => $tags->p('Hello')));
    }
}
