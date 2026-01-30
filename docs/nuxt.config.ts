// https://nuxt.com/docs/api/configuration/nuxt-config
const baseURL = process.env.NUXT_APP_BASE_URL || '/'
const docsVersion = process.env.DOCS_VERSION || '4.x'

export default defineNuxtConfig({
    extends: 'docus',
    modules: ['@nuxt/image', '@nuxt/scripts', 'nuxt-fathom'],
    fathom: {
        siteId: process.env.NUXT_PUBLIC_FATHOM_SITE_ID || '',
    },
    devtools: { enabled: true },
    site: {
        name: 'Flowforge',
    },
    runtimeConfig: {
        public: {
            docsVersion,
        },
    },
    appConfig: {
        docus: {
            url: `https://relaticle.github.io${baseURL}`,
            image: `${baseURL}preview.png`,
            header: {
                logo: {
                    light: `${baseURL}logo-light.svg`,
                    dark: `${baseURL}logo-dark.svg`,
                },
            },
        },
        seo: {
            ogImage: `${baseURL}preview.png`,
        },
        github: {
            branch: docsVersion,
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
