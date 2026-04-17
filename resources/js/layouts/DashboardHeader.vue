<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const roles = computed(() => page.props.auth?.roles ?? [])
const canAccessNotifications = computed(() => roles.value.includes('super_admin') || roles.value.includes('admin'))
const notifications = ref([])
const isLoading = ref(false)

onMounted(() => {
  if (canAccessNotifications.value) {
    fetchNotifications()
  }
})

async function fetchNotifications() {
  isLoading.value = true

  try {
    const response = await axios.get('/dashboard/notifications/data')
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
</script>

<template>
  <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/85 backdrop-blur">
    <div class="flex items-center justify-between px-4 py-3 sm:px-6">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Dashboard</p>
        <h1 class="text-lg font-bold text-slate-900">ETEC Control Center</h1>
      </div>

      <div class="flex items-center gap-4">
        <button
          v-if="canAccessNotifications"
          type="button"
          class="relative cursor-pointer rounded-lg p-1 transition hover:bg-slate-100"
          :disabled="isLoading"
          @click="goToNotifications"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

        <div class="hidden text-right sm:block">
          <p class="text-sm font-semibold text-slate-800">{{ user?.name ?? 'Guest' }}</p>
          <p class="text-xs text-slate-500">{{ user?.email ?? 'Not signed in' }}</p>
        </div>

        <Link
          href="/logout"
          method="post"
          as="button"
          class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
        >
          Logout
        </Link>
      </div>
    </div>
  </header>
</template>
