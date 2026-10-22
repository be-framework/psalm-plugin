# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial extraction of the plugin from `be-framework/be` (`src/Psalm/` in that repo).
- `MissingBeingParameterAttribute` issue: reports Being constructor parameters lacking both `#[Input]` and `#[Inject]`.
- `ConflictingBeingParameterAttribute` issue: reports Being constructor parameters that carry both `#[Input]` and `#[Inject]`.
- `InvalidValidateException` issue: reports `#[Validate]` methods that throw exceptions not extending `\DomainException`.
- Respects class-level `@psalm-suppress` for all three issues.
- Black-box integration tests running the actual `psalm` binary against fixture files.

### Fixed

- `InputTaintHandler::addTaints()` now returns the Psalm 7 `int` `TaintKind` bitmask instead of a `list<string>`, matching the `AddTaintsInterface` contract (`vimeo/psalm ^7.0@dev`). The `^7.0@dev` requirement was declared in a prior change but the handler itself was never updated, so loading the plugin against Psalm 7 fatal-errored with a `Declaration ... must be compatible with AddTaintsInterface::addTaints()` error.
- Added `@psalm-pure` / `@psalm-capabilities` annotations Psalm 7's stricter purity checker now requires on `InputTaintHandler`'s private helpers; removed the now-inaccurate `@psalm-external-mutation-free` on `ValidateThrowHandler::extendsDomainException()` (it calls `Codebase::classOrInterfaceExists()`, which Psalm 7 correctly treats as not mutation-free).
