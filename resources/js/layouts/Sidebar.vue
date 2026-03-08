<script setup>
import { ref } from "vue"
import menu from "../config/sidebar"

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
<aside class="w-[260px] bg-[#0f1117] border-r border-white/5 h-screen sticky top-0 flex flex-col overflow-hidden font-sans">

    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/5">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white font-bold text-sm shadow-[0_0_0_1px_rgba(99,102,241,0.4),0_4px_12px_rgba(99,102,241,0.25)]">
            E
        </div>
        <span class="text-white font-semibold text-[15px] tracking-wide">ETEC</span>
    </div>

    <!-- User Pill -->
    <div class="mx-3 mt-3 mb-1 flex items-center gap-2.5 px-3 py-2.5 bg-white/[0.04] border border-white/[0.07] rounded-xl cursor-pointer hover:bg-white/[0.07] transition-colors">
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-500 to-violet-400 flex items-center justify-center text-white text-xs font-semibold shrink-0">
            {{ user.name?.[0]?.toUpperCase() ?? "U" }}
        </div>
        <div class="flex flex-col flex-1 min-w-0">
            <span class="text-gray-200 text-[13px] font-medium truncate">{{ user.name ?? "Admin" }}</span>
            <span class="text-gray-500 text-[11px] capitalize">{{ user.role.replace("_", " ") }}</span>
        </div>
        <svg class="w-3.5 h-3.5 text-gray-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9l6 6 6-6"/>
        </svg>
    </div>

    <!-- Nav -->
    <nav class="flex-1 overflow-y-auto px-3 py-3 scrollbar-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

        <div v-for="section in filteredMenu" :key="section.section" class="mb-5">

            <!-- Section label -->
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-700 px-2 mb-1.5">
                {{ section.section }}
            </p>

            <ul class="flex flex-col gap-px">
                <li v-for="item in section.items" :key="item.name">

                    <!-- Normal link -->
                    <a
                        v-if="!item.children"
                        :href="item.route"
                        :class="[
                            'relative flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13.5px] transition-colors no-underline',
                            isActive(item.route)
                                ? 'bg-indigo-500/15 text-indigo-300 font-medium before:absolute before:left-0 before:top-[20%] before:h-[60%] before:w-[3px] before:bg-indigo-500 before:rounded-r-sm'
                                : 'text-gray-400 hover:bg-white/[0.05] hover:text-gray-200'
                        ]"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-40 shrink-0"></span>
                        <span>{{ item.name }}</span>
                        <span v-if="item.badge" class="ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-400">
                            {{ item.badge }}
                        </span>
                    </a>

                    <!-- Dropdown -->
                    <div v-else>
                        <button
                            @click="toggleDropdown(item.name)"
                            :class="[
                                'w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-[13.5px] transition-colors text-left border-none bg-transparent cursor-pointer',
                                openDropdowns[item.name]
                                    ? 'text-gray-200 bg-white/[0.05]'
                                    : 'text-gray-400 hover:bg-white/[0.05] hover:text-gray-200'
                            ]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-40 shrink-0"></span>
                            <span>{{ item.name }}</span>
                            <svg
                                :class="['ml-auto w-3.5 h-3.5 text-gray-600 transition-transform duration-200', openDropdowns[item.name] ? 'rotate-90' : '']"
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
                            <ul v-show="openDropdowns[item.name]" class="ml-[18px] mt-0.5 pl-3 border-l border-white/[0.06] flex flex-col gap-px">
                                <li v-for="child in item.children" :key="child.name">
                                    <a
                                        :href="child.route"
                                        :class="[
                                            'flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[13px] transition-colors no-underline',
                                            isActive(child.route)
                                                ? 'text-indigo-300'
                                                : 'text-gray-500 hover:bg-white/[0.04] hover:text-gray-300'
                                        ]"
                                    >
                                        <span class="w-1 h-1 rounded-full bg-current opacity-50 shrink-0"></span>
                                        {{ child.name }}
                                    </a>
                                </li>
                            </ul>
                        </transition>
                    </div>

                </li>
            </ul>

        </div>
    </nav>

    <!-- Footer -->
    <div class="px-3 py-3 border-t border-white/5">
        <a href="/logout" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-gray-600 text-[13px] hover:bg-red-500/[0.08] hover:text-red-400 transition-colors no-underline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 shrink-0">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            Sign out
        </a>
    </div>

</aside>
</template>