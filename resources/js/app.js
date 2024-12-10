import '../css/app.css';
import {createApp, h} from 'vue'
import {createInertiaApp} from '@inertiajs/vue3'
import {createVuetify} from 'vuetify';
import 'vuetify/styles';
import {aliases, mdi} from 'vuetify/iconsets/mdi';
// import '@mdi/font/css/materialdesignicons.css';
import 'vuetify/dist/vuetify.min.css'

const vuetify = createVuetify({
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {mdi},
    },
    theme: {
        defaultTheme: 'light',
    },
});

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', {eager: true})
        return pages[`./Pages/${name}.vue`]
    },
    setup({el, App, props, plugin}) {
        createApp({render: () => h(App, props)})
            .use(plugin)
            .use(vuetify)
            .mount(el)
    },
})
