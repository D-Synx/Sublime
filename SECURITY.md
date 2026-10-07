# Security Policy

We take security seriously and appreciate responsible disclosures.

## Supported versions

The library currently tracks the `main` branch. Please test against the latest commit when reporting issues.

## Trust boundaries

Sublime escapes ordinary text and HTML attribute values. It rejects malformed names, inline `on*` attributes and selected dangerous URL prefixes on `href`, `src`, `action` and `formaction`. This does not make arbitrary HTML, CSS, JavaScript, `srcdoc` or URL destinations safe.

Use `RawHtml`/`raw_html()` only with trusted markup. Treat CSS values and script/style content as trusted, or apply an appropriate contextual policy before building the element. Do not use infinite iterators for children.

## Reporting a vulnerability

* Email `security@darksynx.dev` with a detailed description of the issue, steps to reproduce, and any proof-of-concept code.
* Please **do not** open public GitHub issues for security problems until we have coordinated a fix and disclosure timeline.
* We will acknowledge receipt within 72 hours and aim to provide a remediation timeline as soon as possible.

Thank you for helping keep Sublime secure!

