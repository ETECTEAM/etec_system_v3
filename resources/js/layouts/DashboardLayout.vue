<script setup>
import { ref } from 'vue'
import Sidebar from './Sidebar.vue'
import DashboardHeader from './DashboardHeader.vue'

const isSidebarOpen = ref(false)
const isSidebarCollapsed = ref(false)

function openSidebar() {
    isSidebarOpen.value = true
}

function closeSidebar() {
    isSidebarOpen.value = false
}

function toggleSidebarCollapse() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="flex">
            <Sidebar
                :open="isSidebarOpen"
                :collapsed="isSidebarCollapsed"
                @close="closeSidebar"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <DashboardHeader
                    :sidebar-collapsed="isSidebarCollapsed"
                    @open-sidebar="openSidebar"
                    @toggle-sidebar="toggleSidebarCollapse"
                />

                <main class="flex-1 px-4 pb-10 pt-6 sm:px-6 lg:px-8">
                    <div class="w-full">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
