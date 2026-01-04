@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
## Larastan

- Larastan is a PHPStan extension that adds support for Laravel.
- Never ignore Larastan errors or warnings without approval.
- Use Larastan to verify code correctness and catch potential issues before finalizing changes.
- You can run Larastan with `{{ $assist->binCommand('phpstan') }} analyse` to check for issues.
- Address all Larastan issues before finalizing changes to ensure code quality and adherence to Laravel best practices.
