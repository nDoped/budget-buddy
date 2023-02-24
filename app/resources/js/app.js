import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import Vue3Toasity from 'vue3-toastify';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, Ziggy)
      .use(Vue3Toasity, {
          theme: 'dark',
          autoclose: 1500,
          transition: toast.TRANSITIONS.FLIP,
          //transition: toast.TRANSITIONS.ZOOM,
          //transition: toast.TRANSITIONS.BOUNCE,
          //transition: toast.TRANSITIONS.SLIDE,
          position: toast.POSITION.TOP_RIGHT
        }
      ).mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
