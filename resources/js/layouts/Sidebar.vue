<script setup>
import { ref } from "vue"
import menu from "../config/sidebar"
import { RouterLink } from "vue-router"

const user = window.user ?? { role: "super_admin" }

const filteredMenu = menu
    .filter(section => section.roles.includes(user.role))
    .map(section => ({
        ...section,
        items: section.items.filter(item =>
            !item.roles || item.roles.includes(user.role)
        )
    }))

const openDropdowns = ref({})

function toggleDropdown(name) {
    openDropdowns.value[name] = !openDropdowns.value[name]
}

function isActive(route) {
    return window.location.pathname === route
}
</script>

<template>
<aside class="w-64 bg-blue-950 h-screen sticky top-0 flex flex-col overflow-hidden border-r border-white/10">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 shrink-0">
            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
        </div>
        <span class="text-white font-semibold text-sm tracking-widest">ETEC</span>
        <span class="ml-auto text-[10px] font-semibold text-indigo-300 bg-indigo-500/20 border border-indigo-400/30 px-2 py-0.5 rounded-full">v2</span>
    </div>

    <!-- User Pill -->
    <div class="mx-3 mt-3 mb-1 flex items-center gap-2.5 px-3 py-2.5 bg-white/[0.06] border border-white/10 rounded-2xl cursor-pointer hover:bg-white/10 transition-all duration-150">
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-violet-400 flex items-center justify-center text-white text-xs font-bold shrink-0">
            {{ user.name?.[0]?.toUpperCase() ?? "U" }}
        </div>
        <div class="flex flex-col flex-1 min-w-0">
            <span class="text-white text-[13px] font-medium truncate leading-tight">{{ user.name ?? "Admin" }}</span>
            <span class="text-white text-[11px] capitalize leading-tight">{{ user.role.replace("_", " ") }}</span>
        </div>
        <svg class="w-3.5 h-3.5 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

        <div v-for="section in filteredMenu" :key="section.section" class="mb-6">

            <!-- Section label -->
            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-white/40 px-2.5 mb-2">
                {{ section.section }}
            </p>

            <ul class="flex flex-col gap-0.5">
                <li v-for="item in section.items" :key="item.name">

                    <!-- Normal link -->
                    <RouterLink
                        v-if="!item.children"
                        :to="item.route"
                        :class="[
                            'relative flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-[13.5px] transition-all duration-150 no-underline font-medium',
                            isActive(item.route)
                            ? 'bg-indigo-500/25 text-white before:absolute before:left-0 before:top-[18%] before:h-[64%] before:w-[3px] before:bg-gradient-to-b before:from-indigo-400 before:to-violet-500 before:rounded-r-full'
                            : 'text-white hover:bg-white/[0.06]'
                        ]"
                    >
                        <span :class="['w-1.5 h-1.5 rounded-full shrink-0', isActive(item.route) ? 'bg-indigo-400' : 'bg-white/40']"></span>
                        <span class="flex-1">{{ item.name }}</span>
                        <span v-if="item.badge" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                            {{ item.badge }}
                        </span>
                    </RouterLink>

                    <!-- Dropdown -->
                    <div v-else>
                        <button
                            @click="toggleDropdown(item.name)"
                            :class="[
                                'w-full flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-[13.5px] font-medium transition-all duration-150 text-left border-none bg-transparent cursor-pointer',
                                openDropdowns[item.name]
                                    ? 'text-white bg-white/[0.06]'
                                    : 'text-white hover:bg-white/[0.06]'
                            ]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-white/40 shrink-0"></span>
                            <span class="flex-1">{{ item.name }}</span>
                            <svg
                                :class="['w-3.5 h-3.5 text-white/50 transition-transform duration-200', openDropdowns[item.name] ? 'rotate-90' : '']"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            >
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </button>

                        <transition
                            enter-active-class="transition-all duration-200 ease-out overflow-hidden"
                            leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-96 opacity-100"
                            leave-from-class="max-h-96 opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <ul v-show="openDropdowns[item.name]" class="ml-5 mt-1 pl-3 border-l border-white/10 flex flex-col gap-0.5">
                                <li v-for="child in item.children" :key="child.name">
                                    <RouterLink
                                        :to="child.route"
                                        :class="[
                                            'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[13px] font-medium transition-all duration-150 no-underline',
                                            isActive(child.route)
                                                ? 'text-white bg-indigo-500/15'
                                                : 'text-white hover:bg-white/[0.05]'
                                        ]"
                                    >
                                        <span :class="['w-1 h-1 rounded-full shrink-0', isActive(child.route) ? 'bg-indigo-400' : 'bg-white/30']"></span>
                                        {{ child.name }}
                                    </RouterLink>
                                </li>
                            </ul>
                        </transition>
                    </div>

                </li>
            </ul>

        </div>
    </nav>

    <!-- Footer -->
    <div class="px-3 pb-4 pt-2 border-t border-white/10">
        <a href="/logout" class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-white text-[13px] hover:bg-red-500/10 hover:text-red-300 transition-all duration-150 no-underline font-medium">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 shrink-0">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            Sign out
        </a>
    </div>

</aside>
</template>
