// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    extends: 'docus',
    modules: ["@nuxt/image", "@nuxt/scripts"],
    devtools: {enabled: true},
    app: {
        baseURL: process.env.NODE_ENV === 'production' ? '/flowforge/' : '/'
    },
    image: {
        provider: 'none'
    },
    content: {
        build: {
            markdown: {
                highlight: {
                    langs: [
                        'php'
                    ]
                }
            }
        }
    },
    nitro: {
        preset: 'github_pages'
    }
})
