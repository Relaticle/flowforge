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

const items = computed(() => {
    const currentBasePath = currentVersionConfig.value?.path?.replace(/\/$/, '') || '/flowforge'
    const currentPath = route.path

    return versions.map((version: Version) => {
        const relativePath = currentPath.replace(new RegExp(`^${currentBasePath}`), '')
        const targetPath = version.path.replace(/\/$/, '') + relativePath

        return [{
            label: version.label,
            click: () => {
                window.location.href = targetPath || version.path
            }
        }]
    })
})
</script>

<template>
    <UDropdownMenu
        v-if="versions.length > 1"
        :items="items"
    >
        <UButton
            variant="ghost"
            size="sm"
            :label="currentVersionConfig?.label || currentVersion"
            trailing-icon="i-lucide-chevron-down"
        />
    </UDropdownMenu>
</template>
