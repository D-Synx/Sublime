<?php

declare(strict_types=1);

namespace Sublime;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use Stringable;
use Traversable;

/**
 * Sublime HTML Builder
 *
 * A modern, type-safe, and secure HTML builder for PHP 8.1+
 *
 * @package   Sublime
 * @version   0.1.0
 * @author    DarkSynx
 * @license   MIT
 * @link      https://github.com/DarkSynx/Sublime
 *
 * @psalm-immutable
 */

/**
 * Represents raw HTML content that should not be escaped
 *
 * @psalm-immutable
 */
final class RawHtml implements Stringable
{
    public function __construct(
        public readonly string $html
    ) {
    }

    public function __toString(): string
    {
        return $this->html;
    }
}

/**
 * Eagerly captures child values; escaping happens once at their output context.
 *
 * @internal
 */
final class ChildValues
{
    /** @return list<HtmlElement|RawHtml|string> */
    public static function normalize(mixed $value): array
    {
        $children = [];
        $ancestors = [];
        self::append($value, $children, 0, $ancestors);
        return $children;
    }

    /**
     * @param list<HtmlElement|RawHtml|string> $children
     * @param array<int, true> $ancestors
     */
    private static function append(mixed $value, array &$children, int $depth, array &$ancestors): void
    {
        if ($value === null || $value === false) {
            return;
        }
        if ($value instanceof HtmlElement || $value instanceof RawHtml) {
            $children[] = $value;
            return;
        }
        if (is_array($value) || $value instanceof Traversable) {
            if ($depth >= 128) {
                throw new InvalidArgumentException('Child containers cannot exceed 128 nested levels.');
            }
            $id = is_object($value) ? spl_object_id($value) : null;
            if ($id !== null && isset($ancestors[$id])) {
                throw new InvalidArgumentException('Recursive child iterator is not supported.');
            }
            if ($id !== null) {
                $ancestors[$id] = true;
            }
            try {
                foreach ($value as $item) {
                    self::append($item, $children, $depth + 1, $ancestors);
                }
            } finally {
                if ($id !== null) {
                    unset($ancestors[$id]);
                }
            }
            return;
        }
        if (is_scalar($value) || $value instanceof Stringable) {
            $children[] = (string) $value;
            return;
        }
        throw new InvalidArgumentException('Unsupported child value: ' . get_debug_type($value));
    }

    public static function render(HtmlElement|RawHtml|string $child): string
    {
        if ($child instanceof HtmlElement) {
            return $child->render();
        }
        if ($child instanceof RawHtml) {
            return (string) $child;
        }
        return htmlspecialchars($child, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Fluent factory that exposes every HTML element helper as a dynamic method.
 *
 * The factory is automatically injected into {@see Sublime()} callbacks that
 * declare at least one parameter, which allows importing just the main
 * rendering function while still having access to all helpers via
 * `$tags->div(...)`, `$tags->body(...)`, etc.
 */
final class TagFactory
{
    /**
     * Dynamically proxy method calls to {@see HtmlElement::create()}.
     *
     * @param string $name Method name representing the HTML tag.
     * @param array<int, mixed> $arguments Arguments forwarded to the element.
     */
    public function __call(string $name, array $arguments): HtmlElement
    {
        return $this->tag($this->normalizeTagName($name), ...$arguments);
    }

    /**
     * Explicitly create an element from a tag name.
     */
    public function tag(string $tag, mixed ...$args): HtmlElement
    {
        return HtmlElement::create($tag, ...$args);
    }

    /**
     * Forward helper to create raw HTML content.
     */
    public function raw(string $html): RawHtml
    {
        return raw_html($html);
    }

    /**
     * Forward helper to render a complete HTML document.
     */
    public function document(HtmlElement $html): string
    {
        return document($html);
    }

    /**
     * Forward helper to render fragments.
     */
    public function fragment(mixed ...$children): string
{
    $html = '';
    foreach ($children as $child) {
        foreach (ChildValues::normalize($child) as $item) {
            $html .= ChildValues::render($item);
        }
    }
    return $html;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Create raw HTML (use with caution)
 *
 * @param string $html
 * @return RawHtml
 */
function raw_html(string $html): RawHtml
{
    return new RawHtml($html);
}

/**
 * Generic tag creator
 *
 * @param string $tag
 * @param mixed ...$args
 * @return HtmlElement
 */
function _tag(string $tag, mixed ...$args): HtmlElement
{
    return HtmlElement::create($tag, ...$args);
}

/**
 * Create HTML document with proper DOCTYPE
 *
 * @param HtmlElement $html
 * @return string
 */
function document(HtmlElement $html): string
{
    return "<!DOCTYPE html>\n" . $html->render();
}

/**
 * Fragment wrapper (no tag, just children)
 *
 * @param mixed ...$children
 * @return string
 */
function fragment(mixed ...$children): string
{
    $normalized = [];
    foreach ($children as $child) {
        if ($child instanceof HtmlElement) {
            $normalized[] = $child->render();
        } elseif ($child instanceof RawHtml) {
            $normalized[] = (string) $child;
        } elseif (is_array($child)) {
            $normalized[] = fragment(...$child);
        } else {
            $normalized[] = htmlspecialchars((string) $child, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    return implode('', $normalized);
}

// ============================================================================
// HTML ELEMENT FUNCTIONS (with underscore suffix)
// ============================================================================

// Document structure
function html_(mixed ...$args): HtmlElement
{
    return _tag('html', ...$args);
}

function head_(mixed ...$args): HtmlElement
{
    return _tag('head', ...$args);
}

function body_(mixed ...$args): HtmlElement
{
    return _tag('body', ...$args);
}

function title_(mixed ...$args): HtmlElement
{
    return _tag('title', ...$args);
}

function meta_(mixed ...$args): HtmlElement
{
    return _tag('meta', ...$args);
}

function link_(mixed ...$args): HtmlElement
{
    return _tag('link', ...$args);
}

function style_(mixed ...$args): HtmlElement
{
    return _tag('style', ...$args);
}

function script_(mixed ...$args): HtmlElement
{
    return _tag('script', ...$args);
}

// Content sectioning
function header_(mixed ...$args): HtmlElement
{
    return _tag('header', ...$args);
}

function footer_(mixed ...$args): HtmlElement
{
    return _tag('footer', ...$args);
}

function main_(mixed ...$args): HtmlElement
{
    return _tag('main', ...$args);
}

function section_(mixed ...$args): HtmlElement
{
    return _tag('section', ...$args);
}

function article_(mixed ...$args): HtmlElement
{
    return _tag('article', ...$args);
}

function aside_(mixed ...$args): HtmlElement
{
    return _tag('aside', ...$args);
}

function nav_(mixed ...$args): HtmlElement
{
    return _tag('nav', ...$args);
}

// Text content
function div_(mixed ...$args): HtmlElement
{
    return _tag('div', ...$args);
}

function span_(mixed ...$args): HtmlElement
{
    return _tag('span', ...$args);
}

function p_(mixed ...$args): HtmlElement
{
    return _tag('p', ...$args);
}

function h1_(mixed ...$args): HtmlElement
{
    return _tag('h1', ...$args);
}

function h2_(mixed ...$args): HtmlElement
{
    return _tag('h2', ...$args);
}

function h3_(mixed ...$args): HtmlElement
{
    return _tag('h3', ...$args);
}

function h4_(mixed ...$args): HtmlElement
{
    return _tag('h4', ...$args);
}

function h5_(mixed ...$args): HtmlElement
{
    return _tag('h5', ...$args);
}

function h6_(mixed ...$args): HtmlElement
{
    return _tag('h6', ...$args);
}

function blockquote_(mixed ...$args): HtmlElement
{
    return _tag('blockquote', ...$args);
}

function pre_(mixed ...$args): HtmlElement
{
    return _tag('pre', ...$args);
}

// Inline text semantics
function a_(mixed ...$args): HtmlElement
{
    return _tag('a', ...$args);
}

function strong_(mixed ...$args): HtmlElement
{
    return _tag('strong', ...$args);
}

function em_(mixed ...$args): HtmlElement
{
    return _tag('em', ...$args);
}

function code_(mixed ...$args): HtmlElement
{
    return _tag('code', ...$args);
}

function small_(mixed ...$args): HtmlElement
{
    return _tag('small', ...$args);
}

function mark_(mixed ...$args): HtmlElement
{
    return _tag('mark', ...$args);
}

function del_(mixed ...$args): HtmlElement
{
    return _tag('del', ...$args);
}

function ins_(mixed ...$args): HtmlElement
{
    return _tag('ins', ...$args);
}

function sub_(mixed ...$args): HtmlElement
{
    return _tag('sub', ...$args);
}

function sup_(mixed ...$args): HtmlElement
{
    return _tag('sup', ...$args);
}

function ruby_(mixed ...$args): HtmlElement
{
    return _tag('ruby', ...$args);
}

// Lists
function ul_(mixed ...$args): HtmlElement
{
    return _tag('ul', ...$args);
}

function ol_(mixed ...$args): HtmlElement
{
    return _tag('ol', ...$args);
}

function li_(mixed ...$args): HtmlElement
{
    return _tag('li', ...$args);
}

function dl_(mixed ...$args): HtmlElement
{
    return _tag('dl', ...$args);
}

function dt_(mixed ...$args): HtmlElement
{
    return _tag('dt', ...$args);
}

function dd_(mixed ...$args): HtmlElement
{
    return _tag('dd', ...$args);
}

// Media
function img_(mixed ...$args): HtmlElement
{
    return _tag('img', ...$args);
}

function video_(mixed ...$args): HtmlElement
{
    return _tag('video', ...$args);
}

function audio_(mixed ...$args): HtmlElement
{
    return _tag('audio', ...$args);
}

function source_(mixed ...$args): HtmlElement
{
    return _tag('source', ...$args);
}

function picture_(mixed ...$args): HtmlElement
{
    return _tag('picture', ...$args);
}

function canvas_(mixed ...$args): HtmlElement
{
    return _tag('canvas', ...$args);
}

function svg_(mixed ...$args): HtmlElement
{
    return _tag('svg', ...$args);
}

// Forms
function form_(mixed ...$args): HtmlElement
{
    return _tag('form', ...$args);
}

function input_(mixed ...$args): HtmlElement
{
    return _tag('input', ...$args);
}

function button_(mixed ...$args): HtmlElement
{
    return _tag('button', ...$args);
}

function select_(mixed ...$args): HtmlElement
{
    return _tag('select', ...$args);
}

function option_(mixed ...$args): HtmlElement
{
    return _tag('option', ...$args);
}

function textarea_(mixed ...$args): HtmlElement
{
    return _tag('textarea', ...$args);
}

function label_(mixed ...$args): HtmlElement
{
    return _tag('label', ...$args);
}

function fieldset_(mixed ...$args): HtmlElement
{
    return _tag('fieldset', ...$args);
}

function legend_(mixed ...$args): HtmlElement
{
    return _tag('legend', ...$args);
}

// Table
function table_(mixed ...$args): HtmlElement
{
    return _tag('table', ...$args);
}

function thead_(mixed ...$args): HtmlElement
{
    return _tag('thead', ...$args);
}

function tbody_(mixed ...$args): HtmlElement
{
    return _tag('tbody', ...$args);
}

function tfoot_(mixed ...$args): HtmlElement
{
    return _tag('tfoot', ...$args);
}

function tr_(mixed ...$args): HtmlElement
{
    return _tag('tr', ...$args);
}

function th_(mixed ...$args): HtmlElement
{
    return _tag('th', ...$args);
}

function td_(mixed ...$args): HtmlElement
{
    return _tag('td', ...$args);
}

function caption_(mixed ...$args): HtmlElement
{
    return _tag('caption', ...$args);
}

function col_(mixed ...$args): HtmlElement
{
    return _tag('col', ...$args);
}

function colgroup_(mixed ...$args): HtmlElement
{
    return _tag('colgroup', ...$args);
}

// Interactive
function details_(mixed ...$args): HtmlElement
{
    return _tag('details', ...$args);
}

function summary_(mixed ...$args): HtmlElement
{
    return _tag('summary', ...$args);
}

function dialog_(mixed ...$args): HtmlElement
{
    return _tag('dialog', ...$args);
}

// Other common elements
function br_(mixed ...$args): HtmlElement
{
    return _tag('br', ...$args);
}

function hr_(mixed ...$args): HtmlElement
{
    return _tag('hr', ...$args);
}

function iframe_(mixed ...$args): HtmlElement
{
    return _tag('iframe', ...$args);
}

function figure_(mixed ...$args): HtmlElement
{
    return _tag('figure', ...$args);
}

function figcaption_(mixed ...$args): HtmlElement
{
    return _tag('figcaption', ...$args);
}

// Additional semantic elements
function base_(mixed ...$args): HtmlElement
{
    return _tag('base', ...$args);
}

function address_(mixed ...$args): HtmlElement
{
    return _tag('address', ...$args);
}

function hgroup_(mixed ...$args): HtmlElement
{
    return _tag('hgroup', ...$args);
}

function search_(mixed ...$args): HtmlElement
{
    return _tag('search', ...$args);
}

function menu_(mixed ...$args): HtmlElement
{
    return _tag('menu', ...$args);
}

function abbr_(mixed ...$args): HtmlElement
{
    return _tag('abbr', ...$args);
}

function b_(mixed ...$args): HtmlElement
{
    return _tag('b', ...$args);
}

function bdi_(mixed ...$args): HtmlElement
{
    return _tag('bdi', ...$args);
}

function bdo_(mixed ...$args): HtmlElement
{
    return _tag('bdo', ...$args);
}

function cite_(mixed ...$args): HtmlElement
{
    return _tag('cite', ...$args);
}

function data_(mixed ...$args): HtmlElement
{
    return _tag('data', ...$args);
}

function dfn_(mixed ...$args): HtmlElement
{
    return _tag('dfn', ...$args);
}

function i_(mixed ...$args): HtmlElement
{
    return _tag('i', ...$args);
}

function kbd_(mixed ...$args): HtmlElement
{
    return _tag('kbd', ...$args);
}

function q_(mixed ...$args): HtmlElement
{
    return _tag('q', ...$args);
}

function rp_(mixed ...$args): HtmlElement
{
    return _tag('rp', ...$args);
}

function rt_(mixed ...$args): HtmlElement
{
    return _tag('rt', ...$args);
}

function s_(mixed ...$args): HtmlElement
{
    return _tag('s', ...$args);
}

function samp_(mixed ...$args): HtmlElement
{
    return _tag('samp', ...$args);
}

function time_(mixed ...$args): HtmlElement
{
    return _tag('time', ...$args);
}

function u_(mixed ...$args): HtmlElement
{
    return _tag('u', ...$args);
}

function var_(mixed ...$args): HtmlElement
{
    return _tag('var', ...$args);
}

function wbr_(mixed ...$args): HtmlElement
{
    return _tag('wbr', ...$args);
}

function area_(mixed ...$args): HtmlElement
{
    return _tag('area', ...$args);
}

function map_(mixed ...$args): HtmlElement
{
    return _tag('map', ...$args);
}

function track_(mixed ...$args): HtmlElement
{
    return _tag('track', ...$args);
}

function embed_(mixed ...$args): HtmlElement
{
    return _tag('embed', ...$args);
}

function object_(mixed ...$args): HtmlElement
{
    return _tag('object', ...$args);
}

function param_(mixed ...$args): HtmlElement
{
    return _tag('param', ...$args);
}

function noscript_(mixed ...$args): HtmlElement
{
    return _tag('noscript', ...$args);
}

function datalist_(mixed ...$args): HtmlElement
{
    return _tag('datalist', ...$args);
}

function meter_(mixed ...$args): HtmlElement
{
    return _tag('meter', ...$args);
}

function optgroup_(mixed ...$args): HtmlElement
{
    return _tag('optgroup', ...$args);
}

function output_(mixed ...$args): HtmlElement
{
    return _tag('output', ...$args);
}

function progress_(mixed ...$args): HtmlElement
{
    return _tag('progress', ...$args);
}

function slot_(mixed ...$args): HtmlElement
{
    return _tag('slot', ...$args);
}

function template_(mixed ...$args): HtmlElement
{
    return _tag('template', ...$args);
}

// ============================================================================
// RENDERING ENGINE
// ============================================================================

/**
 * Main rendering function.
 *
 * If the callback declares at least one parameter, a {@see TagFactory} instance
 * is automatically injected, allowing dynamic access to all helpers without
 * importing them individually.
 *
 * @param HtmlElement|RawHtml|callable|null $data Direct content or a compatibility callback.
 * @param string $class Built-in rendering mode; HTML is the default.
 * @return string
 */
function Sublime(HtmlElement|RawHtml|callable|null $data, string $class = 'html'): string
{
    if ($class !== 'html') {
        throw new InvalidArgumentException("Unknown Sublime mode: {$class}");
    }

    $factory = new TagFactory();
    if ($data !== null && !$data instanceof HtmlElement && !$data instanceof RawHtml) {
        $args = shouldInjectFactory($data) ? [$factory] : [];
        $data = $data(...$args);
    }

    return $factory->fragment($data);
}

/**
 * Determine whether a callback expects the {@see TagFactory} instance.
 */
function shouldInjectFactory(callable $callback): bool
{
    if ($callback instanceof Closure || is_string($callback)) {
        if (is_string($callback) && str_contains($callback, '::')) {
            [$class, $method] = explode('::', $callback, 2);
            $reflection = new ReflectionMethod($class, $method);
        } else {
            $reflection = new ReflectionFunction($callback);
        }
    } elseif (is_array($callback) && count($callback) === 2) {
        $reflection = new ReflectionMethod($callback[0], $callback[1]);
    } elseif (is_object($callback) && method_exists($callback, '__invoke')) {
        $reflection = new ReflectionMethod($callback, '__invoke');
    } else {
        return false;
    }

    return $reflection->getNumberOfParameters() > 0;
}

/**
 * Legacy alias retained for backward compatibility.
 *
 * @deprecated Use {@see Sublime()} instead.
 * @return string
 */
function sublime_(callable $callback): string
{
    return Sublime($callback);
}
