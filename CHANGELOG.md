# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0] — Unreleased

### Added
- Direct composition: `Sublime(body_(...))`; optional `class: 'html', data: ...` mode with no required callback.
- Single-child `data: p_('Hello')`, finite iterable children, frozen Stringable values and a 128-container nesting limit.
- Explicit type errors, conditional class maps, style maps and correct aria/data booleans.
- Tests for child/attribute values, URL checks, void elements, immutable copies, streaming, callable compatibility, README and examples.
- PHP 8.3–8.5 CI gates for tests, static analysis, coding standards and strict optimized Composer autoload.

### Changed
- Minimum PHP is now 8.3; runtime remains dependency-free.
- Composer loads all runtime classes and functions through `autoload.files`.
- Invalid values, non-boolean values on HTML boolean attributes, invalid callback signatures and children on void elements are rejected.
- HTML attribute names are normalized to lowercase; dangerous URL prefixes are checked ignoring ASCII whitespace/control characters.
- Ordinary text uses HTML5 escaping, including `&apos;`; cached, streamed and fragment output share the same rules.
- Documentation and examples lead with direct helper composition. The HTML selector is `class:`.

### Fixed
- Removed duplicate case-only function declaration: PHP already treats `sublime` and `Sublime` as one name.
- Prevented duplicate escaping during immutable copies and overlapping generator keys that lost streamed HTML.
- Preserved the distinct deprecated `sublime_($callback)` compatibility alias.

This entry describes a release candidate, not a published tag or Packagist release.

## [0.1.0] - 2025-11-19
### Added
- Functional HTML builder with immutable `HtmlElement` class and `_tag()` helpers.
- `Sublime()` entry point plus `sublime()` alias for convenience.
- Composer packaging, coding standards, static analysis, and automated tests.
- GitHub Actions CI, contribution docs, issue/PR templates, and security policy.
- Examples and README documentation for installation, usage, and components.

### Changed
- Moved the implementation into `src/Sublime.php` under the `Sublime` namespace.

