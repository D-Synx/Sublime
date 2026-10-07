<?php

declare(strict_types=1);

namespace Sublime\Tests;

use ArrayObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;

use function Sublime\div_;
use function Sublime\fragment;

use Sublime\HtmlElement;

use function Sublime\p_;
use function Sublime\raw_html;

final class ChildrenNormalizationTest extends TestCase
{
    /** @return iterable<string, array{mixed, string}> */
    public static function childValues(): iterable
    {
        yield 'text' => ['<&"\'', '&lt;&amp;&quot;&apos;'];
        yield 'entity text' => ['&amp;', '&amp;amp;'];
        yield 'integer' => [42, '42'];
        yield 'zero' => [0, '0'];
        yield 'float' => [1.5, '1.5'];
        yield 'true' => [true, '1'];
        yield 'false' => [false, ''];
        yield 'null' => [null, ''];
        yield 'element' => [p_('Hello'), '<p>Hello</p>'];
        yield 'raw' => [raw_html('<b>&</b>'), '<b>&</b>'];
        yield 'nested arrays' => [['a', ['b', null, false, [true]]], 'ab1'];
        yield 'iterable' => [new ArrayObject(['a', p_('b')]), 'a<p>b</p>'];
        yield 'stringable' => [new class () implements Stringable {
            public function __toString(): string
            {
                return '<b>&';
            }
        }, '&lt;b&gt;&amp;'];
        yield 'invalid utf8' => ["\xC3(", "\xEF\xBF\xBD("];
    }

    #[DataProvider('childValues')]
    public function testSupportedValuesRenderConsistently(mixed $value, string $expected): void
    {
        self::assertSame('<div>' . $expected . '</div>', div_(data: $value)->render());
        self::assertSame($expected, fragment($value));
    }

    public function testSingleChildAndOrderedChildren(): void
    {
        self::assertSame('<div><p>Hello</p></div>', div_(data: p_('Hello'))->render());
        self::assertSame('<div><p>Hello</p></div>', div_(data: [p_('Hello')])->render());
        self::assertSame('<div><p>One</p><p>Two</p></div>', div_(data: [p_('One'), p_('Two')])->render());
    }

    public function testStringableIsCapturedOnceAtConstruction(): void
    {
        $text = new class () implements Stringable {
            public int $calls = 0;
            public function __toString(): string
            {
                ++$this->calls;
                return '<&';
            }
        };
        $element = div_(data: $text);
        self::assertSame(1, $text->calls);
        self::assertSame('<div>&lt;&amp;</div>', $element->render());
        self::assertSame('<div>&lt;&amp;</div>', implode('', iterator_to_array($element->stream())));
        self::assertSame('<div>&lt;&amp;x</div>', $element->withChildren('x')->render());
        self::assertSame(1, $text->calls);
    }

    public function testGeneratorIsConsumedOnceDuringConstruction(): void
    {
        $started = 0;
        $children = (function () use (&$started) {
            ++$started;
            yield 'first' => '<';
            yield 'second' => [p_('Hi'), false];
        })();
        $element = div_(data: $children);
        self::assertSame(1, $started);
        self::assertSame('<div>&lt;<p>Hi</p></div>', $element->render());
        self::assertSame('<div>&lt;<p>Hi</p></div>', implode('', iterator_to_array($element->stream())));
        self::assertSame(1, $started);
    }

    public function testPublicConstructorUsesTheSameChildRules(): void
    {
        $element = new HtmlElement('div', [], ['<', [p_('Hi')], true, null]);
        self::assertSame('<div>&lt;<p>Hi</p>1</div>', $element->render());
    }

    public function testUnsupportedObjectIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        div_(data: new \stdClass());
    }

    public function testChildClosureIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        div_(data: fn () => 'hidden');
    }

    public function testResourceIsRejected(): void
    {
        $resource = fopen('php://memory', 'r+');
        self::assertIsResource($resource);
        try {
            $this->expectException(InvalidArgumentException::class);
            div_(data: $resource);
        } finally {
            fclose($resource);
        }
    }

    public function testDepth128IsAccepted(): void
    {
        $value = 'x';
        for ($i = 0; $i < 128; ++$i) {
            $value = [$value];
        }
        self::assertSame('<div>x</div>', div_(data: $value)->render());
    }

    public function testDepth129IsRejected(): void
    {
        $value = 'x';
        for ($i = 0; $i < 129; ++$i) {
            $value = [$value];
        }
        $this->expectException(InvalidArgumentException::class);
        div_(data: $value);
    }

    public function testRecursiveIteratorIsRejected(): void
    {
        /** @var ArrayObject<array-key, mixed> $children */
        $children = new ArrayObject();
        $children[] = $children;
        $this->expectException(InvalidArgumentException::class);
        div_(data: $children);
    }

    public function testNestedArraysAreFlattened(): void
    {
        $html = div_(data: [
            'Hello',
            null,
            false,
            ['World', div_('!')]
        ])->render();

        self::assertSame('<div>HelloWorld<div>!</div></div>', $html);
    }
}
