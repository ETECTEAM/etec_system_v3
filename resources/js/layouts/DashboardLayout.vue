<!-- <script setup>
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
</template> -->

<!-- {{-- resources/js/layouts/DashboardLayout.vue --}} -->
<script setup>
import { ref } from 'vue'
import Sidebar from './Sidebar.vue'
import DashboardHeader from './DashboardHeader.vue'
import { ConfirmDialog } from '../components/ui/confirm-dialog'
import { PageLoading } from '../components/ui/page-loading'
import { useRouteLoading } from '../composables/useRouteLoading'

const isSidebarOpen = ref(false)
const isSidebarCollapsed = ref(false)

function openSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value
}

function closeSidebar() {
    isSidebarOpen.value = false
}

function toggleSidebarCollapse() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value
}

// While Inertia is still fetching the destination page, its component hasn't
// mounted yet, so it can't show its own loading state - the still-mounted
// layout from the page being left renders one on its behalf. The current
// page stays rendered underneath (no v-else swap) so the glass overlay has
// real content to blur instead of an empty <main>. PageLoading itself is
// `position: fixed` and mounted as a layout-level sibling (not inside
// <main>) so its blur covers the sidebar and header too, not just the
// content area.
const { isNavigating } = useRouteLoading()
</script>

<template>
    <div class="h-dvh overflow-hidden bg-slate-50 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
        <div class="flex h-full flex-col">
            <div class="shrink-0">
                <DashboardHeader
                    :sidebar-collapsed="isSidebarCollapsed"
                    @open-sidebar="openSidebar"
                    @toggle-sidebar="toggleSidebarCollapse"
                />
            </div>

            <div class="flex min-h-0 flex-1 overflow-hidden">
                <Sidebar
                    :open="isSidebarOpen"
                    :collapsed="isSidebarCollapsed"
                    @close="closeSidebar"
                />

                <main class="min-h-0 flex-1 overflow-y-auto px-4 pb-10 pt-6 sm:px-6 lg:px-8">
                    <div class="w-full">
                        <!-- This is where all page content will appear -->
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <!-- Sibling of the sidebar/header/main flex above, not a descendant of
             any of them - fixed-position so its blur/glass covers the whole
             dashboard (sidebar + navbar + content), not just <main>. -->
        <PageLoading v-if="isNavigating" />

        <!-- Single shared instance: any page can trigger it via useConfirm() -->
        <ConfirmDialog />
    </div>
</template>
