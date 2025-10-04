# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v2.0.6 - 2025-10-04

### What's Changed

* Add German translations for flowforge by @OccTherapist in https://github.com/Relaticle/flowforge/pull/38

### New Contributors

* @OccTherapist made their first contribution in https://github.com/Relaticle/flowforge/pull/38

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.5...v2.0.6

## v2.0.5 - 2025-09-29

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.4...v2.0.5

## v2.0.4 - 2025-09-27

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.3...v2.0.4

## v2.0.3 - 2025-09-13

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.2...v2.0.3

## v2.0.2 - 2025-08-29

### What's Changed

* Update Make Kanban Board Command by @mustafa-online in https://github.com/Relaticle/flowforge/pull/30

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.1...v2.0.2

## v2.0.1 - 2025-08-27

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.0...v2.0.1

## v2.0.0-beta3 - 2025-08-26

### What's Changed

* Use logical properties on column / Fix on RTL by @mustafa-online in https://github.com/Relaticle/flowforge/pull/26

### New Contributors

* @mustafa-online made their first contribution in https://github.com/Relaticle/flowforge/pull/26

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/v2.0.0-beta2...v2.0.0-beta3

## 1.0.0 - 2025-08-20

### What's Changed

* Bump stefanzweifel/git-auto-commit-action from 5 to 6 by @dependabot[bot] in https://github.com/Relaticle/flowforge/pull/15

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/0.2.1...1.0.0

## 1.0.1 - 2025-06-19

**Full Changelog**: https://github.com/Relaticle/flowforge/compare/0.2.1...1.0.1

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
