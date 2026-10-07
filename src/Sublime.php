<?php

declare(strict_types=1);

namespace Sublime;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;
use Stringable;
use Traversable;

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
 * declare exactly one untyped or TagFactory-typed parameter, which allows importing just the main
 * rendering function while still having access to all helpers via
 * `$tags->div(...)`, `$tags->body(...)`, etc.
 *
 * @method HtmlElement html(mixed ...$args)
 * @method HtmlElement html_(mixed ...$args)
 * @method HtmlElement head(mixed ...$args)
 * @method HtmlElement head_(mixed ...$args)
 * @method HtmlElement body(mixed ...$args)
 * @method HtmlElement body_(mixed ...$args)
 * @method HtmlElement title(mixed ...$args)
 * @method HtmlElement title_(mixed ...$args)
 * @method HtmlElement meta(mixed ...$args)
 * @method HtmlElement meta_(mixed ...$args)
 * @method HtmlElement link(mixed ...$args)
 * @method HtmlElement link_(mixed ...$args)
 * @method HtmlElement style(mixed ...$args)
 * @method HtmlElement style_(mixed ...$args)
 * @method HtmlElement script(mixed ...$args)
 * @method HtmlElement script_(mixed ...$args)
 * @method HtmlElement header(mixed ...$args)
 * @method HtmlElement header_(mixed ...$args)
 * @method HtmlElement footer(mixed ...$args)
 * @method HtmlElement footer_(mixed ...$args)
 * @method HtmlElement main(mixed ...$args)
 * @method HtmlElement main_(mixed ...$args)
 * @method HtmlElement section(mixed ...$args)
 * @method HtmlElement section_(mixed ...$args)
 * @method HtmlElement article(mixed ...$args)
 * @method HtmlElement article_(mixed ...$args)
 * @method HtmlElement aside(mixed ...$args)
 * @method HtmlElement aside_(mixed ...$args)
 * @method HtmlElement nav(mixed ...$args)
 * @method HtmlElement nav_(mixed ...$args)
 * @method HtmlElement div(mixed ...$args)
 * @method HtmlElement div_(mixed ...$args)
 * @method HtmlElement span(mixed ...$args)
 * @method HtmlElement span_(mixed ...$args)
 * @method HtmlElement p(mixed ...$args)
 * @method HtmlElement p_(mixed ...$args)
 * @method HtmlElement h1(mixed ...$args)
 * @method HtmlElement h1_(mixed ...$args)
 * @method HtmlElement h2(mixed ...$args)
 * @method HtmlElement h2_(mixed ...$args)
 * @method HtmlElement h3(mixed ...$args)
 * @method HtmlElement h3_(mixed ...$args)
 * @method HtmlElement h4(mixed ...$args)
 * @method HtmlElement h4_(mixed ...$args)
 * @method HtmlElement h5(mixed ...$args)
 * @method HtmlElement h5_(mixed ...$args)
 * @method HtmlElement h6(mixed ...$args)
 * @method HtmlElement h6_(mixed ...$args)
 * @method HtmlElement blockquote(mixed ...$args)
 * @method HtmlElement blockquote_(mixed ...$args)
 * @method HtmlElement pre(mixed ...$args)
 * @method HtmlElement pre_(mixed ...$args)
 * @method HtmlElement a(mixed ...$args)
 * @method HtmlElement a_(mixed ...$args)
 * @method HtmlElement strong(mixed ...$args)
 * @method HtmlElement strong_(mixed ...$args)
 * @method HtmlElement em(mixed ...$args)
 * @method HtmlElement em_(mixed ...$args)
 * @method HtmlElement code(mixed ...$args)
 * @method HtmlElement code_(mixed ...$args)
 * @method HtmlElement small(mixed ...$args)
 * @method HtmlElement small_(mixed ...$args)
 * @method HtmlElement mark(mixed ...$args)
 * @method HtmlElement mark_(mixed ...$args)
 * @method HtmlElement del(mixed ...$args)
 * @method HtmlElement del_(mixed ...$args)
 * @method HtmlElement ins(mixed ...$args)
 * @method HtmlElement ins_(mixed ...$args)
 * @method HtmlElement sub(mixed ...$args)
 * @method HtmlElement sub_(mixed ...$args)
 * @method HtmlElement sup(mixed ...$args)
 * @method HtmlElement sup_(mixed ...$args)
 * @method HtmlElement ruby(mixed ...$args)
 * @method HtmlElement ruby_(mixed ...$args)
 * @method HtmlElement ul(mixed ...$args)
 * @method HtmlElement ul_(mixed ...$args)
 * @method HtmlElement ol(mixed ...$args)
 * @method HtmlElement ol_(mixed ...$args)
 * @method HtmlElement li(mixed ...$args)
 * @method HtmlElement li_(mixed ...$args)
 * @method HtmlElement dl(mixed ...$args)
 * @method HtmlElement dl_(mixed ...$args)
 * @method HtmlElement dt(mixed ...$args)
 * @method HtmlElement dt_(mixed ...$args)
 * @method HtmlElement dd(mixed ...$args)
 * @method HtmlElement dd_(mixed ...$args)
 * @method HtmlElement img(mixed ...$args)
 * @method HtmlElement img_(mixed ...$args)
 * @method HtmlElement video(mixed ...$args)
 * @method HtmlElement video_(mixed ...$args)
 * @method HtmlElement audio(mixed ...$args)
 * @method HtmlElement audio_(mixed ...$args)
 * @method HtmlElement source(mixed ...$args)
 * @method HtmlElement source_(mixed ...$args)
 * @method HtmlElement picture(mixed ...$args)
 * @method HtmlElement picture_(mixed ...$args)
 * @method HtmlElement canvas(mixed ...$args)
 * @method HtmlElement canvas_(mixed ...$args)
 * @method HtmlElement svg(mixed ...$args)
 * @method HtmlElement svg_(mixed ...$args)
 * @method HtmlElement form(mixed ...$args)
 * @method HtmlElement form_(mixed ...$args)
 * @method HtmlElement input(mixed ...$args)
 * @method HtmlElement input_(mixed ...$args)
 * @method HtmlElement button(mixed ...$args)
 * @method HtmlElement button_(mixed ...$args)
 * @method HtmlElement select(mixed ...$args)
 * @method HtmlElement select_(mixed ...$args)
 * @method HtmlElement option(mixed ...$args)
 * @method HtmlElement option_(mixed ...$args)
 * @method HtmlElement textarea(mixed ...$args)
 * @method HtmlElement textarea_(mixed ...$args)
 * @method HtmlElement label(mixed ...$args)
 * @method HtmlElement label_(mixed ...$args)
 * @method HtmlElement fieldset(mixed ...$args)
 * @method HtmlElement fieldset_(mixed ...$args)
 * @method HtmlElement legend(mixed ...$args)
 * @method HtmlElement legend_(mixed ...$args)
 * @method HtmlElement table(mixed ...$args)
 * @method HtmlElement table_(mixed ...$args)
 * @method HtmlElement thead(mixed ...$args)
 * @method HtmlElement thead_(mixed ...$args)
 * @method HtmlElement tbody(mixed ...$args)
 * @method HtmlElement tbody_(mixed ...$args)
 * @method HtmlElement tfoot(mixed ...$args)
 * @method HtmlElement tfoot_(mixed ...$args)
 * @method HtmlElement tr(mixed ...$args)
 * @method HtmlElement tr_(mixed ...$args)
 * @method HtmlElement th(mixed ...$args)
 * @method HtmlElement th_(mixed ...$args)
 * @method HtmlElement td(mixed ...$args)
 * @method HtmlElement td_(mixed ...$args)
 * @method HtmlElement caption(mixed ...$args)
 * @method HtmlElement caption_(mixed ...$args)
 * @method HtmlElement col(mixed ...$args)
 * @method HtmlElement col_(mixed ...$args)
 * @method HtmlElement colgroup(mixed ...$args)
 * @method HtmlElement colgroup_(mixed ...$args)
 * @method HtmlElement details(mixed ...$args)
 * @method HtmlElement details_(mixed ...$args)
 * @method HtmlElement summary(mixed ...$args)
 * @method HtmlElement summary_(mixed ...$args)
 * @method HtmlElement dialog(mixed ...$args)
 * @method HtmlElement dialog_(mixed ...$args)
 * @method HtmlElement br(mixed ...$args)
 * @method HtmlElement br_(mixed ...$args)
 * @method HtmlElement hr(mixed ...$args)
 * @method HtmlElement hr_(mixed ...$args)
 * @method HtmlElement iframe(mixed ...$args)
 * @method HtmlElement iframe_(mixed ...$args)
 * @method HtmlElement figure(mixed ...$args)
 * @method HtmlElement figure_(mixed ...$args)
 * @method HtmlElement figcaption(mixed ...$args)
 * @method HtmlElement figcaption_(mixed ...$args)
 * @method HtmlElement base(mixed ...$args)
 * @method HtmlElement base_(mixed ...$args)
 * @method HtmlElement address(mixed ...$args)
 * @method HtmlElement address_(mixed ...$args)
 * @method HtmlElement hgroup(mixed ...$args)
 * @method HtmlElement hgroup_(mixed ...$args)
 * @method HtmlElement search(mixed ...$args)
 * @method HtmlElement search_(mixed ...$args)
 * @method HtmlElement menu(mixed ...$args)
 * @method HtmlElement menu_(mixed ...$args)
 * @method HtmlElement abbr(mixed ...$args)
 * @method HtmlElement abbr_(mixed ...$args)
 * @method HtmlElement b(mixed ...$args)
 * @method HtmlElement b_(mixed ...$args)
 * @method HtmlElement bdi(mixed ...$args)
 * @method HtmlElement bdi_(mixed ...$args)
 * @method HtmlElement bdo(mixed ...$args)
 * @method HtmlElement bdo_(mixed ...$args)
 * @method HtmlElement cite(mixed ...$args)
 * @method HtmlElement cite_(mixed ...$args)
 * @method HtmlElement data(mixed ...$args)
 * @method HtmlElement data_(mixed ...$args)
 * @method HtmlElement dfn(mixed ...$args)
 * @method HtmlElement dfn_(mixed ...$args)
 * @method HtmlElement i(mixed ...$args)
 * @method HtmlElement i_(mixed ...$args)
 * @method HtmlElement kbd(mixed ...$args)
 * @method HtmlElement kbd_(mixed ...$args)
 * @method HtmlElement q(mixed ...$args)
 * @method HtmlElement q_(mixed ...$args)
 * @method HtmlElement rp(mixed ...$args)
 * @method HtmlElement rp_(mixed ...$args)
 * @method HtmlElement rt(mixed ...$args)
 * @method HtmlElement rt_(mixed ...$args)
 * @method HtmlElement s(mixed ...$args)
 * @method HtmlElement s_(mixed ...$args)
 * @method HtmlElement samp(mixed ...$args)
 * @method HtmlElement samp_(mixed ...$args)
 * @method HtmlElement time(mixed ...$args)
 * @method HtmlElement time_(mixed ...$args)
 * @method HtmlElement u(mixed ...$args)
 * @method HtmlElement u_(mixed ...$args)
 * @method HtmlElement var(mixed ...$args)
 * @method HtmlElement var_(mixed ...$args)
 * @method HtmlElement wbr(mixed ...$args)
 * @method HtmlElement wbr_(mixed ...$args)
 * @method HtmlElement area(mixed ...$args)
 * @method HtmlElement area_(mixed ...$args)
 * @method HtmlElement map(mixed ...$args)
 * @method HtmlElement map_(mixed ...$args)
 * @method HtmlElement track(mixed ...$args)
 * @method HtmlElement track_(mixed ...$args)
 * @method HtmlElement embed(mixed ...$args)
 * @method HtmlElement embed_(mixed ...$args)
 * @method HtmlElement object(mixed ...$args)
 * @method HtmlElement object_(mixed ...$args)
 * @method HtmlElement param(mixed ...$args)
 * @method HtmlElement param_(mixed ...$args)
 * @method HtmlElement noscript(mixed ...$args)
 * @method HtmlElement noscript_(mixed ...$args)
 * @method HtmlElement datalist(mixed ...$args)
 * @method HtmlElement datalist_(mixed ...$args)
 * @method HtmlElement meter(mixed ...$args)
 * @method HtmlElement meter_(mixed ...$args)
 * @method HtmlElement optgroup(mixed ...$args)
 * @method HtmlElement optgroup_(mixed ...$args)
 * @method HtmlElement output(mixed ...$args)
 * @method HtmlElement output_(mixed ...$args)
 * @method HtmlElement progress(mixed ...$args)
 * @method HtmlElement progress_(mixed ...$args)
 * @method HtmlElement slot(mixed ...$args)
 * @method HtmlElement slot_(mixed ...$args)
 * @method HtmlElement template(mixed ...$args)
 * @method HtmlElement template_(mixed ...$args)
 */
final class TagFactory
{
    /**
     * Dynamically proxy method calls to {@see HtmlElement::create()}.
     *
     * @param string $name Method name representing the HTML tag.
     * @param array<int|string, mixed> $arguments Arguments forwarded to the element.
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
        return fragment(...$children);
    }

    private function normalizeTagName(string $name): string
    {
        $name = strtolower($name);

        if (str_ends_with($name, '_')) {
            $name = substr($name, 0, -1);
        }

        return $name;
    }
}

/**
 * Immutable HTML Element Builder
 *
 * Features:
 * - Automatic escaping of text and attribute values
 * - Type-safe API with named parameters
 * - Performance optimized with render caching
 * - Standard and custom tag composition
 */
final class HtmlElement implements Stringable
{
    /** @var array<string, true> */
    private const VOID_ELEMENTS = [
        'area' => true, 'base' => true, 'br' => true, 'col' => true,
        'embed' => true, 'hr' => true, 'img' => true, 'input' => true,
        'link' => true, 'meta' => true, 'param' => true, 'source' => true,
        'track' => true, 'wbr' => true
    ];

    /** @var array<string, true> */
    private const BOOLEAN_ATTRS = [
        'disabled' => true, 'readonly' => true, 'required' => true,
        'checked' => true, 'selected' => true, 'multiple' => true,
        'autofocus' => true, 'autoplay' => true, 'controls' => true,
        'loop' => true, 'muted' => true, 'open' => true,
        'reversed' => true, 'novalidate' => true, 'formnovalidate' => true,
        'async' => true, 'defer' => true, 'ismap' => true,
        'itemscope' => true, 'allowfullscreen' => true,
        'inert' => true, 'nomodule' => true, 'playsinline' => true, 'default' => true
    ];

    /** @var array<string, true> */
    private const DANGEROUS_PROTOCOLS = [
        'javascript:' => true,
        'data:text/html' => true,
        'vbscript:' => true
    ];

    private ?string $cachedRender = null;

    /** @var list<HtmlElement|RawHtml|string> */
    private readonly array $children;

    /** @var array<string, string|true> */
    private readonly array $attributes;

    /**
     * @param array<array-key, mixed> $attributes
     * @param array<mixed> $children
     */
    public function __construct(
        private readonly string $tag,
        array $attributes = [],
        array $children = []
    ) {
        $this->validateTag($tag);
        $this->attributes = $this->normalizeAttributes($attributes);
        $normalized = [];
        foreach ($children as $child) {
            foreach (ChildValues::normalize($child) as $item) {
                $normalized[] = $item;
            }
        }
        $this->children = $normalized;
        if ($this->isVoidElement() && $normalized !== []) {
            throw new InvalidArgumentException("Void element {$tag} cannot contain children.");
        }
    }

    /**
     * Create element from flexible arguments
     *
     * @param string $tag HTML tag name
     * @param mixed ...$args Attributes (named) and children (data key or positional)
     * @return self
     *
     * @example
     * div_(class: 'container', data: [h1_('Title')])
     * a_(href: '/home', data: 'Click me')
     */
    public static function create(string $tag, mixed ...$args): self
    {
        $attributes = [];
        $children = [];

        foreach ($args as $key => $value) {
            if (is_int($key)) {
                // Positional argument = child content
                $children[] = $value;
            } elseif ($key === 'data') {
                // Explicit 'data' key = child content
                $children[] = $value;
            } else {
                // Named argument = attribute
                $attributes[$key] = $value;
            }
        }

        return new self($tag, $attributes, $children);
    }

    /**
     * Add child elements (immutable - returns new instance)
     *
     * @param mixed ...$children
     * @return self
     */
    public function withChildren(mixed ...$children): self
    {
        return new self(
            $this->tag,
            $this->attributes,
            array_merge($this->children, $children)
        );
    }

    /**
     * Add or update attributes (immutable - returns new instance)
     *
     * @param array<string, mixed> $attributes
     * @return self
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            $this->tag,
            array_merge($this->attributes, $attributes),
            $this->children
        );
    }

    /**
     * Render to HTML string with caching
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->cachedRender !== null) {
            return $this->cachedRender;
        }

        $html = '<' . $this->tag;
        $html .= $this->renderAttributes();

        if ($this->isVoidElement()) {
            return $this->cachedRender = $html . '>';
        }

        $html .= '>';
        $html .= $this->renderChildren();
        $html .= '</' . $this->tag . '>';

        return $this->cachedRender = $html;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Stream render for large documents (no caching)
     *
     * @return \Generator<string>
     */
    public function stream(): \Generator
    {
        yield '<' . $this->tag;
        yield $this->renderAttributes();

        if ($this->isVoidElement()) {
            yield '>';
            return;
        }

        yield '>';

        foreach ($this->children as $child) {
            if ($child instanceof self) {
                foreach ($child->stream() as $chunk) {
                    yield $chunk;
                }
            } else {
                yield ChildValues::render($child);
            }
        }

        yield '</' . $this->tag . '>';
    }

    /**
     * Render attributes with proper escaping and validation
     *
     * @return string
     */
    private function renderAttributes(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        $parts = [];

        foreach ($this->attributes as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
                continue;
            }
            $escaped = htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $parts[] = sprintf('%s="%s"', $name, $escaped);
        }

        return ' ' . implode(' ', $parts);
    }

    /**
     * Render child elements
     *
     * @return string
     */
    private function renderChildren(): string
    {
        return implode('', array_map(
            ChildValues::render(...),
            $this->children
        ));
    }

    /**
     * Render style array to CSS string
     *
     * @param array<array-key, mixed> $styles
     * @return string
     */
    private function renderStyleArray(array $styles): string
    {
        $parts = [];
        foreach ($styles as $property => $value) {
            if (!is_string($property) || !preg_match('/\A(?:--[a-z0-9_-]+|-?[a-z][a-z0-9-]*)\z/i', $property)) {
                throw new InvalidArgumentException('Invalid CSS property name.');
            }
            if ($value === null || $value === false || $value === '') {
                continue;
            }
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException("Unsupported CSS value for {$property}.");
            }
            $parts[] = $property . ':' . $value;
        }
        return implode(';', $parts);
    }

    /**
     * Check if element is void (self-closing)
     *
     * @return bool
     */
    private function isVoidElement(): bool
    {
        return isset(self::VOID_ELEMENTS[strtolower($this->tag)]);
    }

    /**
     * Validate tag name
     *
     * @param string $tag
     * @throws InvalidArgumentException
     */
    private function validateTag(string $tag): void
    {
        if (!preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)*\z/i', $tag)) {
            throw new InvalidArgumentException("Invalid HTML tag: {$tag}");
        }
    }

    /**
     * Validate and capture attribute values once.
     *
     * @param array<array-key, mixed> $attributes
     * @return array<string, string|true>
     * @throws InvalidArgumentException
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];
        foreach ($attributes as $name => $value) {
            if (!is_string($name) || !preg_match('/\A[a-z][a-z0-9_:-]*\z/i', $name)) {
                throw new InvalidArgumentException("Invalid attribute name: {$name}");
            }
            $name = strtolower($name);
            // A differently-cased duplicate replaces the preceding value.
            unset($normalized[$name]);
            if (str_starts_with($name, 'on')) {
                throw new InvalidArgumentException(
                    "Inline event handlers are not allowed for security. Use addEventListener instead: {$name}"
                );
            }
            if ((str_starts_with($name, 'aria-') || str_starts_with($name, 'data-')) && is_bool($value)) {
                $normalized[$name] = $value ? 'true' : 'false';
                continue;
            }
            if ($value === null || $value === false) {
                continue;
            }
            if (isset(self::BOOLEAN_ATTRS[$name])) {
                if ($value !== true) {
                    throw new InvalidArgumentException("Boolean attribute {$name} requires a boolean.");
                }
                $normalized[$name] = true;
                continue;
            }
            if ($name === 'class' && is_array($value)) {
                $value = $this->renderClassArray($value);
                if ($value === '') {
                    continue;
                }
            } elseif ($name === 'style' && is_array($value)) {
                $value = $this->renderStyleArray($value);
                if ($value === '') {
                    continue;
                }
            }
            if (!is_string($value) && !is_int($value) && !is_float($value) && !$value instanceof Stringable) {
                throw new InvalidArgumentException("Unsupported value for attribute {$name}: " . get_debug_type($value));
            }
            $value = (string) $value;
            if (in_array($name, ['href', 'src', 'action', 'formaction'], true)) {
                $this->validateUrl($value);
            }
            $normalized[$name] = $value;
        }
        return $normalized;
    }

    /** @param array<array-key, mixed> $classes */
    private function renderClassArray(array $classes): string
    {
        $parts = [];
        $list = array_is_list($classes);
        foreach ($classes as $name => $value) {
            if ($list) {
                if (!is_string($value)) {
                    throw new InvalidArgumentException('Class lists require strings.');
                }
                $class = trim($value);
            } else {
                if (!is_bool($value)) {
                    throw new InvalidArgumentException('Class maps require boolean values.');
                }
                if (!$value) {
                    continue;
                }
                $class = trim((string) $name);
            }
            if ($class !== '') {
                $parts[] = $class;
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Validate URL for dangerous protocols
     *
     * @param string $url
     * @throws InvalidArgumentException
     */
    private function validateUrl(string $url): void
    {
        $url = strtolower((string) preg_replace('/[\x00-\x20\x7F]/', '', $url));

        foreach (self::DANGEROUS_PROTOCOLS as $protocol => $_) {
            if (str_starts_with($url, $protocol)) {
                throw new InvalidArgumentException(
                    "Dangerous protocol detected in URL: {$protocol}"
                );
            }
        }
    }
}

/**
 * Component trait for creating reusable components
 */
trait Component
{
    /**
     * Render the component
     *
     * @return HtmlElement
     */
    abstract public function render(): HtmlElement;

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render()->render();
    }
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
    $html = '';
    foreach ($children as $child) {
        foreach (ChildValues::normalize($child) as $item) {
            $html .= ChildValues::render($item);
        }
    }
    return $html;
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
 * Render a prebuilt element without a callback. HTML is the default mode.
 * Positional callbacks are retained for compatibility; validated one-parameter
 * callbacks receive a {@see TagFactory} instance.
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
    $parameters = (new ReflectionFunction(Closure::fromCallable($callback)))->getParameters();
    if ($parameters === []) {
        return false;
    }
    if (count($parameters) !== 1) {
        throw new InvalidArgumentException('Sublime callback must declare zero or one parameter.');
    }
    $parameter = $parameters[0];
    $type = $parameter->getType();
    if ($parameter->isVariadic() || $parameter->isPassedByReference()
        || ($type !== null && (!$type instanceof ReflectionNamedType
            || strcasecmp($type->getName(), TagFactory::class) !== 0))) {
        throw new InvalidArgumentException('Sublime callback parameter must be untyped or TagFactory-typed, by value and non-variadic.');
    }
    return true;
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
