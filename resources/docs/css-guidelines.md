# Flowforge CSS Structure and Guidelines

This document outlines the CSS architecture, naming conventions, and best practices for the Flowforge plugin.

## Table of Contents

1. [CSS Architecture](#css-architecture)
2. [Naming Conventions](#naming-conventions)
3. [Component Structure](#component-structure)
4. [Color System](#color-system)
5. [Responsive Design](#responsive-design)
6. [Best Practices](#best-practices)

## CSS Architecture

The Flowforge CSS is structured using a modified BEM (Block Element Modifier) methodology with the following organization:

```
resources/css/flowforge.css
├── Base variables and theme configuration
├── Layout Components
├── UI Components
├── Color Systems
└── Utility Classes
```

### 1. Base Variables and Theme Configuration

Root variables are defined for consistent spacing, colors, and component styling. These variables support both light and dark mode themes.

### 2. Layout Components

Layout components control the overall structure of the UI, including:
- Board layouts
- Column layouts
- Containers

### 3. UI Components

UI components are individual interface elements:
- Cards
- Badges
- Buttons
- Forms

### 4. Color Systems

Color classes provide consistent styling across the application:
- Maintains existing `kanban-color-*` classes
- Ensures consistent light/dark mode support

### 5. Utility Classes

Small, single-purpose classes for common styling needs:
- `.ff-truncate`
- `.ff-clickable`
- `.ff-sortable`

## Naming Conventions

### Modified BEM Methodology

We use a modified BEM naming convention with a `ff-` prefix (Flowforge):

```
.ff-[block]__[element]--[modifier]
```

**Examples:**
- `.ff-card` - The card component (block)
- `.ff-card__title` - The title within a card (element)
- `.ff-card--priority-high` - High priority variation (modifier)
- `.ff-badge--sm` - Small badge variation (modifier)

### Naming Rules

1. Use kebab-case for all class names
2. Use descriptive, semantic names
3. Prefix all custom classes with `ff-`
4. Use double underscore (`__`) to separate elements
5. Use double dash (`--`) for modifiers

## Component Structure

### Card Component

```html
<div class="ff-card ff-card--priority-high">
    <div class="ff-card__body">
        <h4 class="ff-card__title">Card Title</h4>
        <p class="ff-card__description">Description text</p>
        <div class="ff-card__badges">
            <!-- Badges go here -->
        </div>
    </div>
</div>
```

### Badge Component

```html
<div class="ff-badge ff-badge--sm ff-badge--pill kanban-color-red">
    <span class="ff-badge__label">Label</span>
    <span class="ff-badge__value">Value</span>
</div>
```

### Column Component

```html
<div class="ff-column">
    <div class="ff-column__header">
        <div class="ff-column__title">
            Column Title
            <div class="ff-column__count kanban-color-blue">5</div>
        </div>
    </div>
    <div class="ff-column__content">
        <!-- Cards go here -->
    </div>
</div>
```

## Color System

The color system is based on Tailwind CSS colors with custom variations for better UI/UX. Each color has specific light and dark mode variants.

### Available Colors

Flowforge uses the following color palette:
- Neutrals: default, white, slate, gray, zinc, neutral, stone
- Accent: red, orange, amber, yellow, lime, green, emerald, teal
- Brand: cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose

### Usage

1. **For badges and indicators:** Use the `kanban-color-*` classes
2. **For solid badges:** Use both `ff-badge--solid` and `ff-badge--red` (or other color) classes
3. **For text and borders:** Apply specific Tailwind classes as needed

## Responsive Design

Flowforge uses a mobile-first approach to responsive design.

### Responsive Strategy

1. **Component-based responsive styles:** Most components have built-in responsive behavior
2. **Custom responsive classes:** Use `@screen` directives in the CSS for component variations
3. **Tailwind breakpoints:** For specific cases, use Tailwind's responsive prefixes directly in markup

### Breakpoints

We follow Tailwind's default breakpoints:
- `sm`: 640px and up
- `md`: 768px and up
- `lg`: 1024px and up
- `xl`: 1280px and up
- `2xl`: 1536px and up

## Best Practices

### When to Use BEM Classes vs. Tailwind Utilities

1. **Use BEM classes for:**
   - Component structure and layout
   - Repeated patterns and consistent elements
   - Component variations and states

2. **Use Tailwind utilities for:**
   - One-off styling needs
   - Fine-tuning spacing, alignment, or typography
   - Responsive adjustments

### Adding New Components

When adding new components:

1. Add the component's BEM classes to `flowforge.css`
2. Follow the existing naming conventions
3. Use CSS variables for theming when appropriate
4. Document the component structure and variations

### Modifying Existing Components

When modifying components:

1. Check if a modifier already exists for your use case
2. Add new modifiers using the `--modifier` syntax
3. Update documentation if adding significant new functionality

### Dark Mode Support

All components should support dark mode:

1. Use CSS variables when possible
2. Use Tailwind's `dark:` prefix for specific overrides
3. Test all components in both light and dark modes

---

This guide should be considered a living document. Update it as patterns and best practices evolve. 