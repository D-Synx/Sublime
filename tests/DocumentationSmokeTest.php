<?php

declare(strict_types=1);

namespace Sublime\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentationSmokeTest extends TestCase
{
    /**
     * @param list<string> $arguments
     * @return array{int, string, string}
     */
    private function runPhp(array $arguments): array
    {
        $process = proc_open([PHP_BINARY, ...$arguments], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__));
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertIsString($stdout);
        self::assertIsString($stderr);
        return [proc_close($process), $stdout, $stderr];
    }

    public function testReadmeQuickStartRunsAsWritten(): void
    {
        $readme = file_get_contents(dirname(__DIR__) . '/README.md');
        self::assertIsString($readme);
        self::assertSame(1, preg_match('/[\x60]{3}php\n(.*?)[\x60]{3}/s', $readme, $matches));
        $script = preg_replace('/\A<\?php\s*/', '', $matches[1]);
        self::assertIsString($script);
        [$status, $output, $stderr] = $this->runPhp(['-r', $script]);
        self::assertSame(0, $status, $stderr);
        self::assertSame('', $stderr);
        self::assertSame('<body><div class="app"><p>Hello</p></div></body>', $output);
    }

    public function testComposerBootstrapSupportsMultipleChildren(): void
    {
        $script = 'require "vendor/autoload.php";'
            . 'echo \Sublime\Sublime(class: "html", data: \Sublime\div_(class: "app", data: [\Sublime\p_("One"), \Sublime\p_("Two")]));';
        [$status, $output, $stderr] = $this->runPhp(['-r', $script]);
        self::assertSame(0, $status, $stderr);
        self::assertSame('', $stderr);
        self::assertSame('<div class="app"><p>One</p><p>Two</p></div>', $output);
    }

    /** @return iterable<string, array{string, string}> */
    public static function examples(): iterable
    {
        yield 'basic' => ['examples/basic.php', '<body><div class="app"><p>Hello</p></div></body>'];
        yield 'components' => ['examples/components.php', '<body class="layout"><nav><a href="/">Accueil</a><a href="/docs">Documentation</a></nav><main><section class="card"><h2>Composants</h2><p>Composez des fonctions PHP.</p></section><section class="card"><h2>Simple</h2><p>Un seul enfant sans tableau.</p></section></main><footer><small>Créé avec Sublime</small></footer></body>'];
        yield 'conditions' => ['examples/conditions.php', '<body><div class="page"><h1>Bienvenue</h1><ul><li>Nouveauté &amp; simplicité</li><li>Documentation mise à jour</li></ul></div></body>'];
        yield 'index' => ['index.php', '<!DOCTYPE html>' . "\n" . '<html lang="fr"><head><meta charset="utf-8"><title>Sublime</title></head><body><main class="app"><h1>Sublime</h1><p>Du HTML simple en PHP.</p></main></body></html>'];
    }

    #[DataProvider('examples')]
    public function testBundledExampleRuns(string $file, string $expected): void
    {
        [$status, $output, $stderr] = $this->runPhp([$file]);
        self::assertSame(0, $status, $stderr);
        self::assertSame('', $stderr);
        self::assertSame($expected, $output);
    }
}
