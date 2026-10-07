<?php

declare(strict_types=1);

namespace Sublime\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sublime\HtmlElement;

final class HtmlValidationTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function invalidNames(): iterable
    {
        foreach (['', '1div', 'div x', 'div>', "div\n", 'div--x', 'div-'] as $name) {
            yield json_encode($name, JSON_THROW_ON_ERROR) => [$name];
        }
    }

    #[DataProvider('invalidNames')]
    public function testInvalidTagName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HtmlElement($name);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidAttributeNames(): iterable
    {
        foreach (['', '1id', 'id x', 'id=', "id\n", 'onclick', 'OnLoad'] as $name) {
            yield json_encode($name, JSON_THROW_ON_ERROR) => [$name];
        }
    }

    #[DataProvider('invalidAttributeNames')]
    public function testInvalidAttributeName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HtmlElement('div', [$name => 'value']);
    }

    public function testCustomTagAndAttributeNames(): void
    {
        self::assertSame('<my-card data-id="1" aria-label="Card"></my-card>', (new HtmlElement('my-card', ['data-id' => 1, 'aria-label' => 'Card']))->render());
    }
}
