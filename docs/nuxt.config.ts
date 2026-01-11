// https://nuxt.com/docs/api/configuration/nuxt-config
const baseURL = process.env.NUXT_APP_BASE_URL || '/'
const docsVersion = process.env.DOCS_VERSION || 'v3'

export default defineNuxtConfig({
    extends: 'docus',
    modules: ['@nuxt/image', '@nuxt/scripts'],
    devtools: { enabled: true },
    site: {
        name: 'Flowforge',
    },
    appConfig: {
        versioning: {
            current: docsVersion,
        },
    },
    app: {
        baseURL,
        buildAssetsDir: 'assets',
        head: {
            link: [
                {
                    rel: 'icon',
                    type: 'image/x-icon',
                    href: baseURL + 'favicon.ico',
                },
            ],
        },
    },
    image: {
        provider: 'none',
    },
    content: {
        build: {
            markdown: {
                highlight: {
                    langs: ['php', 'blade'],
                },
            },
        },
    },
    llms: {
        domain: `https://relaticle.github.io${baseURL.replace(/\/$/, '')}`,
    },
    nitro: {
        preset: 'github_pages',
    },
})
