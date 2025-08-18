# Flowforge Development Guide

This document provides guidance for contributors looking to enhance or modify the Flowforge package.

## 🏗️ Architecture Overview

Flowforge follows a clean, modular architecture:

### Core Components

- **BoardPage**: Abstract Filament page class that serves as the foundation for boards
- **KanbanBoard**: Livewire component that handles the UI and interactions
- **KanbanConfig**: Immutable configuration class for board settings
- **KanbanAdapterInterface**: Contract for data operations between models and the board

### Adapters

- **DefaultKanbanAdapter**: Standard implementation for Eloquent models
- Custom adapters can be created for complex scenarios (e.g., custom fields, relationships)

### Traits

- **CardFormattingTrait**: Handles card display formatting
- **CrudOperationsTrait**: Manages create, read, update, and delete operations
- **QueryHandlingTrait**: Handles database queries and search functionality

## 🧩 Directory Structure

```
src/
├── Adapters/            # Data adapters for models/queries
├── Commands/            # Artisan commands (MakeKanbanBoardCommand)
├── Config/              # Configuration classes
├── Concerns/            # Trait implementations
├── Contracts/           # Interfaces (KanbanAdapterInterface)
├── Enums/               # Enum classes
├── Facades/             # Laravel facades
├── Filament/            # Filament integration
│   ├── Pages/           # BoardPage base class
│   └── Resources/       # Filament resource integration 
├── Livewire/            # Livewire components
│   └── Components/      # KanbanBoard component
├── Providers/           # Service providers
├── Support/             # Helper classes
└── Testing/             # Testing utilities
```

## 🚀 Development Environment Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/relaticle/flowforge.git
   cd flowforge
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Set up a Laravel test application (recommended):
   ```bash
   composer create-project laravel/laravel:^11.0 flowforge-test
   cd flowforge-test
   composer require filament/filament:^3.0
   ```

4. Link your local Flowforge development version:
   ```bash
   # In your flowforge-test composer.json, add:
   "repositories": [
       {
           "type": "path",
           "url": "../flowforge"
       }
   ]
   ```

5. Require the local package:
   ```bash
   composer require relaticle/flowforge:@dev
   ```

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run specific test files:

```bash
composer test -- --filter=KanbanBoardTest
```

## 🔄 Development Workflow

1. Create a new branch for your feature or fix:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make your changes, following the coding standards (PSR-12)

3. Write or update tests to cover your changes

4. Run the test suite to ensure all tests pass:
   ```bash
   composer test
   ```

5. Submit a pull request with a clear description of your changes

## 📝 Coding Standards

This package follows PSR-12 coding standards. You can check your code with:

```bash
composer lint
```

And automatically fix most issues with:

```bash
composer format
```

## 🔧 Common Development Tasks

### Adding a New Configuration Option

1. Add the property to `KanbanConfig` class
2. Add a setter method in the `BoardPage` class
3. Update the Livewire component to use the new configuration
4. Add tests for the new feature
5. Document the new option in README.md

### Creating a New Command

1. Create a new class in `src/Commands/` directory
2. Register the command in `FlowforgeServiceProvider`
3. Add tests for the command
4. Document the command in README.md

### Modifying the Kanban UI

1. Locate the appropriate Livewire component in `src/Livewire/Components/`
2. Make your changes to the component class or view
3. Rebuild assets if necessary
4. Test your changes in a real Laravel application
5. Update documentation if the change affects user experience

## 📚 Documentation Guidelines

When updating or adding features, please also update:

1. The main README.md file with usage examples
2. PHPDoc comments in relevant classes
3. This DEVELOPMENT.md file if architecture changes

## 🤝 Getting Help

If you have questions about development:

1. Open an issue on GitHub
2. Check existing issues and discussions
3. Refer to the Filament documentation for UI component guidelines

## 📋 Release Process

1. Update the version number in `composer.json`
2. Update CHANGELOG.md with the changes in the new version
3. Create a new release on GitHub with release notes
4. Tag the release with the appropriate version number 