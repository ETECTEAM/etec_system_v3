<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const emit = defineEmits(['open-sidebar'])

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const roles = computed(() => page.props.auth?.roles ?? [])
const canAccessNotifications = computed(() => roles.value.includes('super_admin') || roles.value.includes('admin'))
const notifications = ref([])
const isLoading = ref(false)
const profileOpen = ref(false)
const profileRef = ref(null)

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

onMounted(() => {
  if (canAccessNotifications.value) {
    fetchNotifications()
  }

  document.addEventListener('click', handleDocumentClick)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('keydown', handleEscape)
})

async function fetchNotifications() {
  isLoading.value = true

  try {
    const response = await axios.get('/notifications/data')
    const payload = response.data?.data ?? response.data ?? []
    notifications.value = Array.isArray(payload) ? payload : []
  } catch (error) {
    console.error('Failed to fetch notifications', error)
    notifications.value = []
  } finally {
    isLoading.value = false
  }
}

function goToNotifications() {
  router.visit('/dashboard/notifications')
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
}

function handleEscape(event) {
  if (event.key === 'Escape') {
    closeProfile()
  }
}
</script>

<template>
  <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="rounded-lg border border-slate-200 p-1.5 text-slate-600 transition hover:bg-slate-50 lg:hidden"
          @click="emit('open-sidebar')"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75ZM3 10a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
          </svg>
        </button>

        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Dashboard</p>
          <h1 class="text-lg font-semibold text-slate-900">ETEC Control Center</h1>
        </div>
      </div>

      <div class="flex items-center gap-3 sm:gap-4">
        <button
          v-if="canAccessNotifications"
          type="button"
          class="relative rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 transition hover:bg-slate-50"
          :disabled="isLoading"
          @click="goToNotifications"
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
            v-if="notifications.length"
            class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-500 px-1 text-center text-xs font-semibold text-white"
          >
            {{ notifications.length }}
          </span>
        </button>

        <div ref="profileRef" class="relative">
          <button
            type="button"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-left transition hover:bg-slate-50"
            @click="toggleProfile"
          >
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
              {{ initials }}
            </span>
            <span class="hidden sm:block">
              <span class="block text-sm font-semibold text-slate-800">{{ user?.name ?? 'Guest' }}</span>
              <span class="block text-xs text-slate-500">{{ user?.email ?? 'Not signed in' }}</span>
            </span>
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 0 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
            </svg>
          </button>

          <div
            v-if="profileOpen"
            class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          >
            <div class="px-4 py-3">
              <p class="text-sm font-semibold text-slate-900">{{ user?.name ?? 'Guest' }}</p>
              <p class="text-xs text-slate-500">{{ user?.email ?? 'Not signed in' }}</p>
            </div>
            <div class="border-t border-slate-200">
              <Link
                href="/logout"
                method="post"
                as="button"
                class="w-full px-4 py-2 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
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
