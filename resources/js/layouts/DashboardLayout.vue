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
import RolePermissionSkeleton from '../pages/backend/users/components/RolePermissionSkeleton.vue'
import { useRouteLoading } from '../composables/useRouteLoading'

const isSidebarOpen = ref(false)

function openSidebar() {
    isSidebarOpen.value = true
}

function closeSidebar() {
    isSidebarOpen.value = false
}

// While Inertia is still fetching the destination page, its component hasn't
// mounted yet, so it can't show its own skeleton — the still-mounted layout
// from the page being left has to render one on its behalf, keyed off the URL
// being navigated to.
const { isNavigating, targetUrl } = useRouteLoading()
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
        <div class="flex">
            <Sidebar :open="isSidebarOpen" @close="closeSidebar" />

            <div class="flex min-w-0 flex-1 flex-col">
                <DashboardHeader @open-sidebar="openSidebar" />

                <main class="flex-1 px-4 pb-10 pt-6 sm:px-6 lg:px-8">
                    <div class="w-full">
                        <RolePermissionSkeleton v-if="isNavigating && targetUrl === '/dashboard/users/roles'" />
                        <!-- This is where all page content will appear -->
                        <slot v-else />
                    </div>
                </main>
            </div>
        </div>

        <!-- Single shared instance: any page can trigger it via useConfirm() -->
        <ConfirmDialog />
    </div>
</template>