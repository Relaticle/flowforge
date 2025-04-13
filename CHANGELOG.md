# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
