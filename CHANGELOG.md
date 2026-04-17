# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

### Fixed

- Dragging a card to the top of a column no longer snaps it to the bottom. Extended the SortableJS top-drop workaround so the DOM is normalized before neighbors are read, and switched to reading them from the DOM directly rather than the stale internal sortable array (#110)

## v3.0.5 - 2026-04-10

### Security

- Update axios to 1.15.0 to resolve critical SSRF and DoS vulnerabilities
- Update brace-expansion to fix process hang vulnerability
- Update immutable to fix prototype pollution vulnerability

## v3.0.3 - 2026-03-06

### Fixed

- Cast record ID to string in `formatBoardRecord()` to prevent JavaScript precision loss with large integer IDs (snowflakes) (#88, #91)

## v3.0.2 - 2026-01-28

### Fixed

- Fixed card ID attributes not being present for drag operations, preventing potential errors during card moves

## v3.0.1 - 2026-01-28

### Bug Fixes

- Import DB facade for database operations in HasBoardRecords (#80)

## [3.0.0] - 2025-12-26

### Breaking Changes

- **Position column type changed** from `VARCHAR` to `DECIMAL(20,10)`
- **New dependency**: `ext-bcmath` PHP extension required
- **Removed**: `Rank.php` service (Lexorank algorithm)
- **Laravel version**: Now requires Laravel 12+

### Added

- `DecimalPosition` service with BCMath-based position calculations
- `PositionRebalancer` service for automatic gap management
- Cryptographic jitter (±5%) prevents concurrent insertion collisions
- Auto-rebalancing when gap falls below 0.0001
- Retry mechanism with exponential backoff (50ms, 100ms, 200ms)
- `MaxRetriesExceededException` for conflict handling
- `flowforge:diagnose-positions` command - detect gaps, inversions, duplicates
- `flowforge:rebalance-positions` command - redistribute positions evenly
- Support for custom primary keys via `getKeyName()`
- Comprehensive logging of rebalancing operations
- `UPGRADE.md` migration guide for v2.x users

### Changed

- Position algorithm from Lexorank (string) to DecimalPosition (decimal)
- Blueprint macro `flowforgePositionColumn()` now creates `DECIMAL(20,10)`
- `flowforge:repair-positions` command now interactive with multiple strategies

### Removed

- `Rank.php` service
- String-based position calculations
- Binary collation requirements

### Migration

See [UPGRADE.md](UPGRADE.md) for detailed migration instructions from v2.x.


---

## [2.1.0] - Previous stable release

See [v2.x branch](https://github.com/Relaticle/flowforge/tree/2.x) for v2.x changelog.


---

## 0.2.1 - 2025-05-29

### What's Changed

* Bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 by @dependabot in https://github.com/Relaticle/flowforge/pull/10
* Fix empty translation file causing array_replace_recursive() error by @vasilGerginski in https://github.com/Relaticle/flowforge/pull/13

### New Contributors

* @dependabot made their first contribution in https://github.com/Relaticle/flowforge/pull/10
* @vasilGerginski made their first contribution in https://github.com/Relaticle/flowforge/pull/13

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/0.2.0...0.2.1

## 0.2.0 - 2025-04-22

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/0.1.9...0.2.0

## 0.1.9 - 2025-04-16

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/0.1.7...0.1.9

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/0.1.7...0.1.9

## [Unreleased]

### Added

- Enhanced developer experience with improved documentation
- New QUICK-START.md guide for rapid onboarding
- New DEVELOPMENT.md guide for contributors
- Restructured README.md with better organization and examples
- Model existence validation in generator command
- Detailed troubleshooting section with common solutions
- Comprehensive examples for all configuration options
- Clear distinction between required and optional methods
- Added read-only board implementation examples
- Added separate stub files for create and edit actions

### Changed

- Completely redesigned code generation approach for true minimalism
- Removed all PHPDocs from generated files for cleaner code
- Radically simplified MakeKanbanBoardCommand to only ask for board name and model
- Removed all interactive prompts for configuration options
- Always generates a minimal read-only board as starting point
- Reduced comments and unnecessary code in generated files
- Enhanced stub templates for minimal, clean implementation
- Reorganized documentation with clearer structure
- Improved error messages and validation in code generator
- Clarified that createAction() and editAction() methods are optional
- Made generated code reflect the optional nature of interactive features
- Simplified documentation for minimal implementation
- Improved modularity by separating method templates into dedicated files
- Adopted a true "convention over configuration" approach for better DX

## [1.0.0] - 2023-04-XX

### Added

- Initial release
