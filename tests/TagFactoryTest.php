<?php

declare(strict_types=1);

namespace Sublime\Tests;

use PHPUnit\Framework\TestCase;

use function Sublime\Sublime;

use Sublime\TagFactory;

use function Sublime\body_;
use function Sublime\div_;
use function Sublime\p_;

final class TagFactoryTest extends TestCase
{
    public function testDirectNamedFactoryAndCallbackFormsAreEquivalent(): void
    {
        $factory = new TagFactory();
        $element = body_(data: div_(class: 'app', data: p_('Hello')));
        $expected = '<body><div class="app"><p>Hello</p></div></body>';
        self::assertSame($expected, Sublime($element));
        self::assertSame($expected, Sublime(class: 'html', data: $element));
        self::assertSame($expected, Sublime($factory->body(data: $factory->div(class: 'app', data: $factory->p('Hello')))));
        self::assertSame($expected, Sublime(fn (TagFactory $tags) => $tags->body(data: $tags->div(class: 'app', data: $tags->p('Hello')))));
    }

    public function testDynamicHelpersAndExplicitFactoryUtilities(): void
    {
        $factory = new TagFactory();
        self::assertSame('<p>Hello</p>', $factory->p_('Hello')->render());
        self::assertSame('<my-card>Hello</my-card>', $factory->tag('my-card', 'Hello')->render());
        self::assertSame('&lt;<b>Trusted</b>', $factory->fragment('<', $factory->raw('<b>Trusted</b>')));
        self::assertSame('<!DOCTYPE html>' . "\n" . '<html></html>', $factory->document($factory->tag('html')));
    }

    public function testFactoryInjectionProvidesDynamicTags(): void
    {
        $html = Sublime(function (TagFactory $tags): mixed {
            return $tags->body(
                data: [
                    $tags->div(
                        class: 'wrapper',
                        data: [
                            $tags->p('Hello from the factory'),
                        ],
                    ),
                ],
            );
        });

        self::assertSame('<body><div class="wrapper"><p>Hello from the factory</p></div></body>', $html);
    }

    public function testFactorySupportsMethodsWithoutTrailingUnderscore(): void
    {
        $factory = new TagFactory();

        $element = $factory->main(
            data: [
                $factory->p('Content'),
            ],
        );

        self::assertSame('<main><p>Content</p></main>', $element->render());
    }
}
