<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const notifications = ref([])
const isLoading = ref(false)

onMounted(() => {
  fetchNotifications()
})

async function fetchNotifications() {
  isLoading.value = true

  try {
    const res = await axios.get('/notifications/data')
    const payload = res.data?.data ?? res.data ?? []
    notifications.value = Array.isArray(payload) ? payload : []
  } catch (error) {
    console.error('Failed to fetch notifications', error)
    notifications.value = []
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <Head title="Notifications" />

  <DashboardLayout>
    <section class="space-y-4 p-4 sm:p-6">
      <div class="rounded-2xl bg-linear-to-r from-blue-950 via-blue-900 to-sky-800 p-5 text-white shadow-sm">
        <h1 class="text-xl font-semibold">Notifications</h1>
        <p class="mt-1 text-sm text-blue-100/90">Recent pending verification events and system updates.</p>
      </div>

      <div v-if="isLoading" class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        Loading notifications...
      </div>

      <div v-else-if="notifications.length === 0" class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        No notifications found.
      </div>

      <div v-else class="space-y-2">
        <article
          v-for="n in notifications"
          :key="n.id"
          class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
          <h2 class="font-semibold text-slate-800 dark:text-gray-100">{{ n.title }}</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ n.message }}</p>
        </article>
      </div>
    </section>
  </DashboardLayout>
</template>
