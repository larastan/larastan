# Larastan contributor guidance

Larastan adds Laravel-aware analysis to PHPStan. Find the nearest existing
implementation and follow its complete pattern before adding a new one.

## Sources of truth

- `composer.json` defines supported PHP, Laravel, PHPStan, and PHPUnit versions.
- `extension.neon` defines services, extension tags, parameters, and defaults.
- `CONTRIBUTING.md` documents setup and contribution expectations.
- `.github/workflows/` defines the complete CI matrix.

Do not copy version numbers or CI matrices into documentation. Link to their
source instead so guidance does not drift.

## Where a change belongs

### For source code
- Dynamic return types belong in `src/ReturnTypes/`.
- Method and property discovery extensions belong in `src/Methods/` and
  `src/Properties/`; reusable PHPStan reflection implementations belong in
  `src/Reflection/`.
- Custom PHPStan types and type-node resolvers belong in `src/Types/`.
- Diagnostics belong in `src/Rules/`; cross-file data belongs in
  `src/Collectors/`.
- PHPStan stubs belong in `stubs/`.

### For tests
- Isolated behavior belongs in `tests/Unit/`, reflection extension behavior in
  `tests/Reflection/`, inline type inference in `tests/Type/`, rule diagnostics
  in `tests/Rules/`, and full-file analysis in `tests/Integration/`.
- Shared application classes, configuration, migrations, views, and providers
  belong in `tests/application/`.
- External-application compatibility belongs in `e2e/`.

Keep related implementation, registration, tests, and documentation together.
Check sibling extensions for the full set of files a change requires.

## Before changing behavior

- Reproduce a bug with the smallest test that fails before the fix.
- When existing behavior looks deliberate or version-dependent, inspect git
  history and the relevant Laravel and PHPStan source, tests, PRs, or issues
  before replacing it.
- Establish framework semantics from upstream tests or a focused runtime probe
  before encoding them as PHPStan types.
- Before creating an extension, check whether PHPDoc in stubs, including
  generics, conditional return types, and assertions, can express the behavior
  clearly and accurately. Prefer stubs when they can; otherwise use the smallest
  dedicated extension point that fixes the root cause. Avoid speculative
  helpers, generic machinery, and broad refactors.
- Keep unrelated discoveries out of the current change; report or fix them
  separately.
- If a test describes correct runtime behavior, fix Larastan's implementation,
  stub, or declaration instead of weakening the assertion.
- Keep inferred types both sound and useful on realistic Laravel code. Do not
  broaden a type merely to satisfy a test or narrow it merely to hide errors.
- Do not silence new failures with the baseline or ignore rules.

## Extension wiring and contracts

- Register extensions in `extension.neon` with the tag required by their
  PHPStan interface. For feature-gated tags or explicit `active` arguments,
  follow the nearest existing registration pattern.
- Add every Larastan-owned public configuration parameter to both `parameters`
  and `parametersSchema`, then document it in
  `docs/custom-config-parameters.md` or, for rule-specific settings, alongside
  the rule in `docs/rules.md`.
- New diagnostic identifiers use the `larastan.` prefix and dotted camelCase
  segments, such as `larastan.console.undefinedArgument`.
  `rules.modelAppends` is a legacy outlier; do not copy it.
- Document user-visible rules in `docs/rules.md`, custom types in
  `docs/custom-types.md`, and other features in `docs/features.md`.
- Reflection extensions that implement `hasMethod()`/`getMethod()` or
  `hasProperty()`/`getProperty()` must make both calls agree. When `has*()` does
  runtime-, container-, or resolution-dependent discovery, cache the found
  reflection under the same stable key for `get*()`; deterministic extensions
  may reconstruct it.
- For dynamic return type extensions, return `null` when the extension cannot
  refine the call. For other extension types, follow their documented fallback
  semantics. Use `ErrorType` only for a genuinely invalid expression, following
  the nearest sibling.
- PHPStan extension code that resolves services from the booted Laravel
  application should use the existing `HasContainer` mechanism. Its `resolve()`
  method deliberately returns `null` when the application cannot resolve a
  service; preserve that fallback.

## Stub files and Laravel versions

- Shared stubs live in `stubs/common/`; version-specific overrides live in the
  numbered directories.
- Stub discovery keeps only the newest applicable file for each relative path.
  A versioned file therefore replaces the whole earlier file; it is not merged
  declaration by declaration.
- Put a signature in the earliest version where it is valid and add a versioned
  override only where the signature changes.
- Keep version-specific tests at the same boundary as the corresponding stub or
  framework behavior. Use `laravel_version_compare()` like nearby tests.

## PHPStan type handling

- When identifying a type, prefer its query methods or `isSuperTypeOf()`. Use
  `accepts()` for assignment or argument compatibility. Use `instanceof` only
  for representation-specific behavior the `Type` API does not expose.
- Build unions and intersections with `TypeCombinator`.
- Handle all three `TrinaryLogic` outcomes deliberately: yes, no, and maybe.
- For classes from the analysed project, inject `ReflectionProvider` and use
  `hasClass()`/`getClass()` instead of `class_exists()`. Runtime dependency
  checks for Larastan's own dependencies may use `class_exists()`.

## Test fixtures and expectations

- Register every new or renamed type fixture in a `dataFileAsserts()` provider
  via `gatherAssertTypes()`. General fixtures go in `GeneralTypeTest`;
  feature-specific fixtures go in the existing test class for that extension.
  Register integration fixtures in `IntegrationTest::dataIntegrationTests()`.
- Keep fixture class and function names globally unique. Prefer namespaces;
  unnamespaced fixtures are acceptable when their symbols cannot collide.
- Put tests with the behavior they exercise, even when another fixture could
  technically host the assertion.
- Pair integration fixtures with any required code in `tests/application/` and
  use the test configuration that enables the feature under test.
- Put supported-version conditions in the data provider, not inside the
  fixture. Keep the boundary aligned with the corresponding stub or framework
  behavior.
- Type fixtures use inline `assertType()`/`assertNativeType()` assertions. Rule
  and integration fixtures use their existing expected-error formats; keep
  line numbers synchronized with the fixture.
- Integration expectations reject unexpected reported errors but do not prove
  that every item in a partially populated expected-error list was reported.
  When editing such expectations, verify each expected diagnostic actually
  occurs.
- Cover representative boundaries, not only the happy path: supported Laravel
  versions, calls inside and outside the declaring class, unions,
  intersections, nullability, generics, constant and mixed values, objects,
  framework contracts, and backed or unit enums where relevant.
- E2E projects are pinned in `.github/workflows/e2e-tests.yml` and run in CI.
  When one fails, review every added or removed entry in its regenerated
  baseline artifact as a user-visible behavior change.

## Verification

Run the narrowest relevant PHPUnit test or PHPStan path while iterating. Before
considering a change complete, run:

```bash
composer test:cs
composer test:types
composer test:unit
```

Once required checks pass, repeat or broaden verification only for subsequent
changes, failures, or unresolved concerns.

For compatibility-sensitive changes, use the relevant workflow or script in
`.github/workflows/` or `tests/laravel-test.sh` rather than inventing a local
matrix. Run the affected E2E project when a change reaches external Laravel
applications.

When editing `composer.json`, run `composer validate`. After adding or renaming
PHP classes, run `composer dump-autoload --optimize --strict-psr`. Do not mark
new files executable; CI permits only `tests/laravel-test.sh`.
