<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'

const notifications = ref([])
const isLoading = ref(false)
const actioningId = ref(null)

onMounted(() => {
  fetchNotifications()
})

async function fetchNotifications() {
  isLoading.value = true

  try {
    const res = await axios.get('/notifications/data')
    const payload = res.data?.data ?? []
    notifications.value = Array.isArray(payload) ? payload : []
  } catch (error) {
    console.error('Failed to fetch notifications', error)
    notifications.value = []
  } finally {
    isLoading.value = false
  }
}

async function actOnNotification(notification, action) {
  actioningId.value = notification.id

  try {
    const response = await axios.post(`/notifications/${notification.id}/${action}`)
    notification.approval_status = response.data?.approval_status ?? notification.approval_status
  } catch (error) {
    console.error(`Failed to ${action} notification`, error)
  } finally {
    actioningId.value = null
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

          <div v-if="n.approval_status === 'pending'" class="mt-3 flex gap-2">
            <button
              type="button"
              class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:opacity-50"
              :disabled="actioningId === n.id"
              @click="actOnNotification(n, 'approve')"
            >
              Approve
            </button>
            <button
              type="button"
              class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
              :disabled="actioningId === n.id"
              @click="actOnNotification(n, 'reject')"
            >
              Reject
            </button>
          </div>
          <p v-else-if="n.approval_status === 'approved'" class="mt-3 text-xs font-semibold text-green-600 dark:text-green-400">
            Approved
          </p>
          <p v-else-if="n.approval_status === 'rejected'" class="mt-3 text-xs font-semibold text-red-600 dark:text-red-400">
            Rejected
          </p>
        </article>
      </div>
    </section>
  </DashboardLayout>
</template>
