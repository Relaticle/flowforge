<script setup lang="ts">
const appConfig = useAppConfig()
const route = useRoute()

interface Version {
    label: string
    value: string
    path: string
}

const versions = (appConfig.versioning?.versions || []) as Version[]
const currentVersion = appConfig.versioning?.current || 'v3'

const currentVersionConfig = computed(() =>
    versions.find((v: Version) => v.value === currentVersion)
)

function getTargetUrl(version: Version): string {
    const currentBasePath = currentVersionConfig.value?.path?.replace(/\/$/, '') || '/flowforge'
    const currentPath = route.path
    const relativePath = currentPath.replace(new RegExp(`^${currentBasePath}`), '')
    return version.path.replace(/\/$/, '') + (relativePath || '/')
}

function switchVersion(version: Version): void {
    const url = getTargetUrl(version)
    window.location.href = url
}
</script>

<template>
    <div v-if="versions.length > 1" class="relative" @click.stop>
        <UPopover>
            <UButton
                variant="ghost"
                size="sm"
                :label="currentVersionConfig?.label || currentVersion"
                trailing-icon="i-lucide-chevron-down"
            />
            <template #content>
                <div class="p-1">
                    <button
                        v-for="version in versions"
                        :key="version.value"
                        class="w-full px-3 py-2 text-left text-sm rounded hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center gap-2"
                        :class="{ 'font-medium text-primary': version.value === currentVersion }"
                        @click="switchVersion(version)"
                    >
                        {{ version.label }}
                    </button>
                </div>
            </template>
        </UPopover>
    </div>
</template>
