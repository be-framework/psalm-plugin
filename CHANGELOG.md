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
