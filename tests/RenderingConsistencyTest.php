<?php

declare(strict_types=1);

namespace Sublime\Tests;

use PHPUnit\Framework\TestCase;
use Sublime\Component;
use Sublime\HtmlElement;

use function Sublime\body_;
use function Sublime\div_;
use function Sublime\document;
use function Sublime\fragment;
use function Sublime\html_;
use function Sublime\p_;
use function Sublime\raw_html;

final class RenderingConsistencyTest extends TestCase
{
    public function testNestedTreeUsesTheSameOutputInEverySurface(): void
    {
        $element = div_(class: ['app' => true, 'off' => false], data: ['<&', p_('Hello'), raw_html('<b>&</b>'), true, false]);
        $expected = '<div class="app">&lt;&amp;<p>Hello</p><b>&</b>1</div>';
        self::assertSame($expected, $element->render());
        self::assertSame($expected, (string) $element);
        self::assertSame($expected, implode('', iterator_to_array($element->stream())));
        self::assertSame($expected, fragment($element));
        self::assertSame('&lt;1<p>Hello</p><b>&</b>', fragment('<', true, null, false, [p_('Hello'), raw_html('<b>&</b>')]));
    }

    public function testDocumentAddsOneDoctype(): void
    {
        self::assertSame('<!DOCTYPE html>' . "\n" . '<html><body><p>Hello</p></body></html>', document(html_(data: body_(data: p_('Hello')))));
    }

    public function testClonesHaveIndependentCachesAndLeaveOriginalUnchanged(): void
    {
        $original = div_(title: '<&', data: '<');
        self::assertSame('<div title="&lt;&amp;">&lt;</div>', $original->render());
        $children = $original->withChildren(p_('Added'), '&');
        $attributes = $original->withAttributes(['title' => null, 'class' => 'copy']);
        self::assertNotSame($original, $children);
        self::assertNotSame($original, $attributes);
        self::assertSame('<div title="&lt;&amp;">&lt;<p>Added</p>&amp;</div>', $children->render());
        self::assertSame('<div class="copy">&lt;</div>', $attributes->render());
        self::assertSame('<div title="&lt;&amp;">&lt;</div>', $original->render());
        self::assertSame($children->render(), implode('', iterator_to_array($children->stream())));
        self::assertSame($attributes->render(), implode('', iterator_to_array($attributes->stream())));
    }

    public function testComponentTraitRendersItsElementAndStringableIsEscapedAsText(): void
    {
        $component = new class {
            use Component;
            public function render(): HtmlElement
            {
                return p_('<&');
            }
        };
        self::assertSame('<p>&lt;&amp;</p>', (string) $component);
        self::assertSame('<div><p>&lt;&amp;</p></div>', div_(data: $component->render())->render());
        self::assertSame('<div>&lt;p&gt;&amp;lt;&amp;amp;&lt;/p&gt;</div>', div_(data: $component)->render());
    }
}
