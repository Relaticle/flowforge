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
            branch: docsVersion === 'v3' ? '3.x' : '2.x',
        },
        versioning: {
            current: docsVersion,
            versions: [
                { label: 'v3 (Latest)', value: 'v3', path: '/flowforge/' },
                { label: 'v2', value: 'v2', path: '/flowforge/v2/' },
            ],
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
