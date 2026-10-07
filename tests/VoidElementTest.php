<?php

declare(strict_types=1);

namespace Sublime\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sublime\HtmlElement;

use function Sublime\img_;

final class VoidElementTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function tags(): iterable
    {
        foreach (['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr', 'IMG'] as $tag) {
            yield $tag => [$tag];
        }
    }

    #[DataProvider('tags')]
    public function testVoidElementHasNoClosingTag(string $tag): void
    {
        $element = new HtmlElement($tag, [], [null, false, []]);
        self::assertSame('<' . $tag . '>', $element->render());
        self::assertSame($element->render(), implode('', iterator_to_array($element->stream())));
    }

    #[DataProvider('tags')]
    public function testVoidElementRejectsChildren(string $tag): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HtmlElement($tag, [], ['child']);
    }

    public function testZeroIsContentAndCannotBeSilentlyDiscarded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        img_(data: 0);
    }

    public function testAddingChildrenToVoidElementIsRejected(): void
    {
        $element = img_(src: '/photo.png');
        self::assertSame('<img src="/photo.png">', $element->render());
        $this->expectException(InvalidArgumentException::class);
        $element->withChildren('child');
    }
}
