<?php

declare(strict_types=1);

namespace Sublime\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;
use Sublime\HtmlElement;

use function Sublime\div_;
use function Sublime\input_;

final class AttributeRenderingTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function attributes(): iterable
    {
        yield 'ordinary scalars' => [['title' => '<&"\'', 'id' => 0, 'tabindex' => 1.5], ' title="&lt;&amp;&quot;&apos;" id="0" tabindex="1.5"'];
        yield 'omit values' => [['title' => null, 'id' => false], ''];
        yield 'aria and data booleans' => [['aria-hidden' => false, 'data-ready' => true], ' aria-hidden="false" data-ready="true"'];
        yield 'class string' => [['class' => 'app active'], ' class="app active"'];
        yield 'class list' => [['class' => ['app', '', 'active']], ' class="app active"'];
        yield 'class map' => [['class' => ['app' => true, 'hidden' => false, 'active' => true]], ' class="app active"'];
        yield 'empty class list' => [['class' => []], ''];
        yield 'style string' => [['style' => 'color:red'], ' style="color:red"'];
        yield 'style map' => [['style' => ['color' => 'red', 'margin-top' => 0, 'opacity' => 0.5, '--accent' => '<&"', 'display' => false, 'width' => null]], ' style="color:red;margin-top:0;opacity:0.5;--accent:&lt;&amp;&quot;"'];
        yield 'empty style map' => [['style' => []], ''];
        yield 'uppercase normalized' => [['CLASS' => 'app', 'DATA-READY' => false], ' class="app" data-ready="false"'];
    }

    #[DataProvider('attributes')]
    /** @param array<string, mixed> $attributes */
    public function testSupportedAttributes(array $attributes, string $expected): void
    {
        $element = div_(...$attributes);
        self::assertSame('<div' . $expected . '></div>', $element->render());
        self::assertSame($element->render(), implode('', iterator_to_array($element->stream())));
    }

    /** @return iterable<string, array{string}> */
    public static function booleanNames(): iterable
    {
        foreach (['disabled', 'readonly', 'required', 'checked', 'selected', 'multiple', 'autofocus', 'autoplay', 'controls', 'loop', 'muted', 'open', 'reversed', 'novalidate', 'formnovalidate', 'async', 'defer', 'ismap', 'itemscope', 'allowfullscreen', 'inert', 'nomodule', 'playsinline', 'default'] as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('booleanNames')]
    public function testBooleanAttributesRequireActualBooleans(string $name): void
    {
        self::assertSame('<input ' . $name . '>', input_(...[$name => true])->render());
        self::assertSame('<input>', input_(...[$name => false])->render());
        self::assertSame('<input>', input_(...[$name => null])->render());
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidAttributes(): iterable
    {
        yield 'ordinary true' => [['title' => true]];
        yield 'ordinary array' => [['title' => ['hello']]];
        yield 'ordinary object' => [['title' => new \stdClass()]];
        yield 'boolean string' => [['disabled' => 'false']];
        yield 'boolean number' => [['disabled' => 1]];
        yield 'class list number' => [['class' => ['app', 1]]];
        yield 'class map string' => [['class' => ['app' => 'yes']]];
        yield 'class nested' => [['class' => [['app']]]];
        yield 'style true' => [['style' => ['color' => true]]];
        yield 'style nested' => [['style' => ['color' => ['red']]]];
        yield 'style invalid property' => [['style' => ['color;x' => 'red']]];
        yield 'style list' => [['style' => ['red']]];
    }

    #[DataProvider('invalidAttributes')]
    /** @param array<string, mixed> $attributes */
    public function testInvalidAttributesFailAtConstruction(array $attributes): void
    {
        $this->expectException(InvalidArgumentException::class);
        div_(...$attributes);
    }

    public function testStringableAttributeIsCapturedOnceAndEscaped(): void
    {
        $value = new class implements Stringable {
            public int $calls = 0;
            public function __toString(): string
            {
                ++$this->calls;
                return '<&"';
            }
        };
        $element = new HtmlElement('div', ['title' => $value]);
        self::assertSame(1, $value->calls);
        self::assertSame('<div title="&lt;&amp;&quot;"></div>', $element->render());
        self::assertSame($element->render(), implode('', iterator_to_array($element->stream())));
        self::assertSame('<div title="&lt;&amp;&quot;" id="clone"></div>', $element->withAttributes(['id' => 'clone'])->render());
        self::assertSame(1, $value->calls);
    }
}
