// https://nuxt.com/docs/api/configuration/nuxt-config
const baseURL = process.env.NUXT_APP_BASE_URL || '/'

export default defineNuxtConfig({
    extends: 'docus',
    modules: ['@nuxt/image', '@nuxt/scripts'],
    devtools: { enabled: true },
    site: {
        name: 'Flowforge',
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
