import { createRouter, createWebHistory } from "vue-router"
import Home from "../pages/backend/Home.vue";


const routes = [
    {path: '/',component: Home},
    {path: '/login',component: Lo},
];

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router
