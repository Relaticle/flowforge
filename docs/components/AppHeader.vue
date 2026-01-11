<script setup lang="ts">
const appConfig = useAppConfig()
const currentVersion = appConfig.versioning?.current || 'v3'
const isOldVersion = currentVersion !== 'v3'
</script>

<template>
    <div>
        <div
            v-if="isOldVersion"
            class="bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 px-4 py-2 text-center text-sm border-b border-amber-200 dark:border-amber-800"
        >
            You are viewing documentation for Flowforge {{ currentVersion }}.
            <a
                href="/flowforge/"
                class="underline font-medium hover:text-amber-900 dark:hover:text-amber-100"
            >
                View the latest version (v3) &rarr;
            </a>
        </div>
        <DocsHeader />
    </div>
</template>
