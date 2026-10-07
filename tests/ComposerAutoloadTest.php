<?php

declare(strict_types=1);

namespace Sublime\Tests;

use PHPUnit\Framework\TestCase;

final class ComposerAutoloadTest extends TestCase
{
    public function testComposerLoadsHelpersWithoutAnotherInclude(): void
    {
        self::assertTrue(function_exists('Sublime\\Sublime'));
        self::assertTrue(function_exists('Sublime\\body_'));
        self::assertTrue(function_exists('Sublime\\p_'));
    }

    public function testStandaloneComposerConsumerRendersHtml(): void
    {
        $script = 'require ' . var_export(dirname(__DIR__) . '/vendor/autoload.php', true)
            . '; echo \\Sublime\\Sublime(\\Sublime\\p_("Hello & <world>"));';
        $output = [];
        $exitCode = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);
        self::assertSame(0, $exitCode, implode("\n", $output));
        self::assertSame('<p>Hello &amp; &lt;world&gt;</p>', implode("\n", $output));
    }
}
