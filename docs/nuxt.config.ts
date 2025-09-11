// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    extends: 'docus',
    modules: ["@nuxt/image", "@nuxt/scripts"],
    devtools: {enabled: true},
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
    }
})