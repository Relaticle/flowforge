// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    extends: 'docus',
    modules: ["@nuxt/image", "@nuxt/scripts"],
    devtools: {enabled: true},
    app: {
        baseURL: process.env.NODE_ENV === 'production' ? '/flowforge/' : '/',
        buildAssetsDir: 'assets', // avoid underscore prefix for GitHub Pages
        head: {
            link: [
                {
                    rel: 'icon',
                    type: 'image/x-icon',
                    href: (process.env.NODE_ENV === 'production' ? '/flowforge/' : '/') + 'favicon.ico'
                }
            ]
        }
    },
    image: {
        // Don't set baseURL for image module - let app.baseURL handle it
        // This prevents double baseURL application
        provider: 'none'
    },
    content: {
        build: {
            markdown: {
                highlight: {
                    langs: [
                        'php',
                        'blade'
                    ]
                }
            }
        }
    },
    nitro: {
        preset: 'github_pages'
    }
})
