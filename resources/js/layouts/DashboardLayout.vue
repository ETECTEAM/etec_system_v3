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
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'
import Sidebar from './Sidebar.vue'
import DashboardHeader from './DashboardHeader.vue'
import { ConfirmDialog } from '../components/ui/confirm-dialog'
import { PageLoading } from '../components/ui/page-loading'
import { useRouteLoading } from '../composables/useRouteLoading'
import { getEcho } from '../echo'

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

// ─── Realtime registration alerts: chime + toast ────────────────────────────
// The backend's NotificationsUpdated event carries no payload by design (the
// REST endpoint stays the source of truth), so on each ping we fetch the feed
// and toast the newest unread item while the chime plays.
const page = usePage()
const toast = useToast()

const isAdmin = computed(() => {
    const roles = page.props.auth?.roles ?? []

    return roles.includes('super_admin') || roles.includes('admin')
})

let notificationChannel = null

function playChime() {
    // Browsers reject autoplay until the user has interacted with the tab at
    // least once - swallow that rejection instead of surfacing it as an error.
    new Audio('/sounds/notification.mp3').play().catch(() => {})
}

async function announceLatestNotification() {
    try {
        const response = await axios.get('/notifications/data')
        const items = Array.isArray(response.data) ? response.data : []
        const latest = items.find((item) => !item.is_read)

        if (latest) {
            toast.info(latest.message ?? latest.title, {
                timeout: 8000,
            })
        }
    } catch (error) {
        console.error('Failed to fetch notifications', error)
    }
}

function handleNotificationsUpdated() {
    playChime()
    announceLatestNotification()
}

onMounted(() => {
    if (!isAdmin.value) return

    notificationChannel = getEcho()?.private('admin-notifications')
        .listen('.notifications.updated', handleNotificationsUpdated)
})

onBeforeUnmount(() => {
    notificationChannel?.stopListening('.notifications.updated', handleNotificationsUpdated)
})
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
