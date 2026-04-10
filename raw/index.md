# 

> 

<u-page-hero>
<template v-slot:title="">

Flowforge

</template>

<template v-slot:description="">

Transform any Laravel model into a production-ready drag-and-drop Kanban board.

Perfect for Filament admin panels and standalone Livewire applications.

</template>

<template v-slot:links="">
<u-button color="neutral" size="xl" to="/getting-started/installation" trailing-icon="i-lucide-arrow-right">

Get started

</u-button>

<u-button color="neutral" size="xl" to="https://github.com/relaticle/flowforge" icon="simple-icons:github" variant="outline">

GitHub

</u-button>
</template>
</u-page-hero>

<div className="text-center,max-w-4xl,mx-auto">

![Flowforge Kanban Board Preview](/preview.png)

</div>

<u-page-section>
<template v-slot:title="">

Why choose Flowforge?

</template>

<template v-slot:features="">
<u-page-feature icon="i-lucide-zap">
<template v-slot:title="">

Enterprise-Scale Performance

</template>

<template v-slot:description="">

Cursor-based pagination handles unlimited cards per column with intelligent loading and position-based ordering optimization.

</template>
</u-page-feature>

<u-page-feature icon="i-lucide-layers">
<template v-slot:title="">

3 Integration Patterns

</template>

<template v-slot:description="">

Use as Filament Pages, Resource integration, or standalone Livewire components - whatever fits your architecture.

</template>
</u-page-feature>

<u-page-feature icon="i-lucide-palette">
<template v-slot:title="">

Rich Card Schemas

</template>

<template v-slot:description="">

Filament Schema builder creates complex card layouts with forms, components, and dynamic content rendering.

</template>
</u-page-feature>

<u-page-feature icon="i-lucide-move">
<template v-slot:title="">

Smart Position Management

</template>

<template v-slot:description="">

Advanced ranking algorithm handles unlimited drag-and-drop ordering with automatic conflict resolution and repair commands.

</template>
</u-page-feature>

<u-page-feature icon="i-lucide-activity">
<template v-slot:title="">

Optimistic UI Experience

</template>

<template v-slot:description="">

Instant visual feedback with loading states, smooth scrolling, and responsive drag interactions for seamless UX.

</template>
</u-page-feature>

<u-page-feature icon="i-lucide-filter">
<template v-slot:title="">

Native Filament Integration

</template>

<template v-slot:description="">

Deep integration with Filament's table system for filters, search, actions, and consistent admin panel experience.

</template>
</u-page-feature>
</template>
</u-page-section>

<u-page-section>
<template v-slot:title="">

Our Ecosystem

</template>

<template v-slot:description="">

Extend your Laravel applications with our ecosystem of complementary tools

</template>

<card-group>
<card icon="i-simple-icons-laravel" target="_blank" title="FilaForms" to="https://filaforms.app">

![FilaForms](https://filaforms.app/img/og-image.png)Visual form builder for all your public-facing forms.

</card>

<card icon="i-lucide-sliders" target="_blank" title="Custom Fields" to="https://relaticle.github.io/custom-fields">

![Custom Fields](https://relaticle.github.io/custom-fields/og-image.png)Let users add custom fields to any model without code changes.

</card>
</card-group>
</u-page-section>
