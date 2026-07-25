<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Moon, Sun, SunMoon } from '@lucide/vue'
import { useTheme } from '@/composables/useTheme'
import { getEcho } from '@/echo'

const props = defineProps({
  sidebarCollapsed: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['open-sidebar', 'toggle-sidebar'])

const { theme, cycleTheme } = useTheme()

// Icon reflects the active mode, including 'system' so it's clear it isn't pinned to light/dark.
const themeIcon = computed(() => {
  if (theme.value === 'dark') return Moon
  if (theme.value === 'light') return Sun
  return SunMoon
})

const themeLabel = computed(() => `Theme: ${theme.value}`)

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const roles = computed(() => page.props.auth?.roles ?? [])
const canAccessNotifications = computed(() => roles.value.includes('super_admin') || roles.value.includes('admin'))
const notifications = ref([])
const unreadCount = ref(0)
const isLoading = ref(false)
const actioningId = ref(null)
const profileOpen = ref(false)
const profileRef = ref(null)
const notificationsOpen = ref(false)
const notificationsRef = ref(null)
let pollTimer = null

const initials = computed(() => {
  const name = user.value?.name ?? ''
  if (!name) {
    return 'U'
  }

  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
})

// Reverb pushes updates instantly; this is just a safety net in case the
// websocket connection drops without reconnecting.
const NOTIFICATIONS_POLL_MS = 60000
let notificationsChannel = null

onMounted(() => {
  if (canAccessNotifications.value) {
    fetchNotifications()
    pollTimer = setInterval(fetchNotifications, NOTIFICATIONS_POLL_MS)

    notificationsChannel = getEcho()
      .private('admin-notifications')
      .listen('.notifications.updated', fetchNotifications)
  }

  document.addEventListener('click', handleDocumentClick)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('keydown', handleEscape)

  if (pollTimer) {
    clearInterval(pollTimer)
  }

  // stopListening (not leave) - Index.vue may share this channel
  // simultaneously, and leave() would drop its subscription too.
  if (notificationsChannel) {
    notificationsChannel.stopListening('.notifications.updated', fetchNotifications)
  }
})

async function fetchNotifications() {
  isLoading.value = true

  try {
    const response = await axios.get('/notifications/data')
    const payload = response.data?.data ?? []
    notifications.value = Array.isArray(payload) ? payload : []
    unreadCount.value = response.data?.unread_count ?? 0
  } catch (error) {
    console.error('Failed to fetch notifications', error)
    notifications.value = []
  } finally {
    isLoading.value = false
  }
}

function toggleNotifications() {
  notificationsOpen.value = !notificationsOpen.value
}

function goToNotifications() {
  notificationsOpen.value = false
  router.visit('/dashboard/notifications')
}

async function actOnNotification(notification, action) {
  actioningId.value = notification.id
  isLoading.value = true

  try {
    const response = await axios.post(`/notifications/${notification.id}/${action}`)
    notification.approval_status = response.data?.approval_status ?? notification.approval_status
    notification.is_read = true
  } catch (error) {
    console.error(`Failed to ${action} notification`, error)
  } finally {
    actioningId.value = null
    isLoading.value = false
    fetchNotifications()
  }
}

function toggleProfile() {
  profileOpen.value = !profileOpen.value
}

function closeProfile() {
  profileOpen.value = false
}

function handleDocumentClick(event) {
  if (!profileRef.value?.contains(event.target)) {
    closeProfile()
  }

  if (!notificationsRef.value?.contains(event.target)) {
    notificationsOpen.value = false
  }
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    closeProfile()
    notificationsOpen.value = false
  }
}
</script>

<template>
  <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
    <div class="flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="rounded-lg border border-slate-200 p-1.5 text-slate-600 transition hover:bg-slate-50 lg:hidden dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
          @click="emit('open-sidebar')"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75ZM3 10a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
          </svg>
        </button>
        <button
          type="button"
          class="hidden rounded-lg border border-slate-200 p-1.5 text-slate-600 transition hover:bg-slate-50 lg:inline-flex dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
          :aria-pressed="props.sidebarCollapsed"
          aria-label="Toggle sidebar"
          @click="emit('toggle-sidebar')"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75ZM3 10a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <div class="flex items-center gap-3 sm:gap-4">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
          :title="themeLabel"
          :aria-label="themeLabel"
          @click="cycleTheme"
        >
          <component :is="themeIcon" class="h-5 w-5" />
        </button>

        <div v-if="canAccessNotifications" ref="notificationsRef" class="relative">
          <button
            type="button"
            class="relative rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            @click="toggleNotifications"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>

            <span
              v-if="unreadCount"
              class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-500 px-1 text-center text-xs font-semibold text-white"
            >
              {{ unreadCount }}
            </span>
          </button>

          <div
            v-if="notificationsOpen"
            class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
          >
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5 dark:border-gray-700">
              <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">Notifications</p>
              <button type="button" class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400" @click="goToNotifications">
                View all
              </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
              <p v-if="isLoading && !notifications.length" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-gray-400">
                Loading...
              </p>
              <p v-else-if="!notifications.length" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-gray-400">
                No notifications yet.
              </p>

              <article
                v-for="n in notifications"
                :key="n.id"
                class="border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-gray-700"
              >
                <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">{{ n.title }}</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">{{ n.message }}</p>

                <div v-if="n.approval_status === 'pending'" class="mt-2 flex gap-2">
                  <button
                    type="button"
                    class="rounded-lg bg-green-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-green-700 disabled:opacity-50"
                    :disabled="actioningId === n.id"
                    @click="actOnNotification(n, 'approve')"
                  >
                    Approve
                  </button>
                  <button
                    type="button"
                    class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                    :disabled="actioningId === n.id"
                    @click="actOnNotification(n, 'reject')"
                  >
                    Reject
                  </button>
                </div>
                <p v-else-if="n.approval_status === 'approved'" class="mt-2 text-xs font-semibold text-green-600 dark:text-green-400">
                  Approved
                </p>
                <p v-else-if="n.approval_status === 'rejected'" class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400">
                  Rejected
                </p>
              </article>
            </div>
          </div>
        </div>

        <div ref="profileRef" class="relative">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-left transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
            @click="toggleProfile"
          >
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
              {{ initials }}
            </span>
            <span class="hidden sm:block">
              <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ user?.name ?? 'Guest' }}</span>
              <span class="block text-xs text-slate-500 dark:text-gray-400">{{ user?.email ?? 'Not signed in' }}</span>
            </span>
            <svg class="h-4 w-4 text-slate-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
            </svg>
          </button>

          <div
            v-if="profileOpen"
            class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
          >
            <div class="px-4 py-3">
              <p class="text-sm font-semibold text-slate-900 dark:text-gray-100">{{ user?.name ?? 'Guest' }}</p>
              <p class="text-xs text-slate-500 dark:text-gray-400">{{ user?.email ?? 'Not signed in' }}</p>
            </div>
            <div class="border-t border-slate-200 dark:border-gray-700">
              <Link
                href="/logout"
                method="post"
                as="button"
                class="w-full cursor-pointer px-4 py-2 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-700"
              >
                Sign out
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
