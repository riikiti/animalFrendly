import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
// Тема применяется до первой отрисовки, чтобы не мигало светлым на тёмной.
import '@/shared/lib/theme'
import App from './App.vue'
import router from './app/router'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
