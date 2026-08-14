<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Bell, Check, Copy, Mail, Trash2 } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { getEcho } from '@/echo'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const notifications = ref([])
const unreadCount = ref(0)
const isLoading = ref(false)
const actioningId = ref(null)
const markingAll = ref(false)
const activeTab = ref('all')

const tabs = computed(() => [
  { key: 'all', label: t('All') },
  { key: 'unread', label: t('Unread') },
  { key: 'pending', label: t('Pending Approval') },
])

const CODE_PATTERN = /^(.*verification code:\s*)(\d+)\s*$/i

function splitMessage(message) {
  const match = CODE_PATTERN.exec(message ?? '')

  if (!match) {
    return { text: message ?? '', code: null }
  }

  return { text: match[1], code: match[2] }
}

const copiedId = ref(null)
let copiedResetTimeout = null

async function copyCode(code, notificationId) {
  try {
    await navigator.clipboard.writeText(code)
  } catch (error) {
    console.error('Failed to copy code', error)
    return
  }

  clearTimeout(copiedResetTimeout)
  copiedId.value = notificationId
  copiedResetTimeout = setTimeout(() => {
    copiedId.value = null
  }, 1500)
}

const filteredNotifications = computed(() => {
  if (activeTab.value === 'unread') {
    return notifications.value.filter((n) => !n.is_read)
  }

  if (activeTab.value === 'pending') {
    return notifications.value.filter((n) => n.approval_status === 'pending')
  }

  return notifications.value
})

let notificationsChannel = null

onMounted(() => {
  fetchNotifications()

  notificationsChannel = getEcho()
    ?.private('admin-notifications')
    .listen('.notifications.updated', fetchNotifications)
})

onBeforeUnmount(() => {
  // stopListening (not leave) - DashboardHeader.vue shares this channel
  // simultaneously, and leave() would drop its subscription too.
  if (notificationsChannel) {
    notificationsChannel.stopListening('.notifications.updated', fetchNotifications)
  }

  clearTimeout(copiedResetTimeout)
})

async function fetchNotifications() {
  // Only the very first load shows the skeleton - background refetches
  // (from the socket broadcast, our own actions, etc.) patch the list in
  // place so it never flashes/looks like the page reloaded.
  const isInitialLoad = notifications.value.length === 0
  if (isInitialLoad) isLoading.value = true

  try {
    const res = await axios.get('/notifications/data')
    const payload = res.data?.data ?? []
    notifications.value = Array.isArray(payload) ? payload : []
    unreadCount.value = res.data?.unread_count ?? 0
  } catch (error) {
    console.error('Failed to fetch notifications', error)
    if (isInitialLoad) notifications.value = []
  } finally {
    if (isInitialLoad) isLoading.value = false
  }
}

async function actOnNotification(notification, action) {
  actioningId.value = notification.id

  try {
    const response = await axios.post(`/notifications/${notification.id}/${action}`)
    notification.approval_status = response.data?.approval_status ?? notification.approval_status
    notification.is_read = true
  } catch (error) {
    console.error(`Failed to ${action} notification`, error)
  } finally {
    actioningId.value = null
    fetchNotifications()
  }
}

async function markRead(notification) {
  if (notification.is_read) return

  notification.is_read = true
  unreadCount.value = Math.max(0, unreadCount.value - 1)

  try {
    await axios.post(`/notifications/${notification.id}/read`)
  } catch (error) {
    console.error('Failed to mark notification as read', error)
    notification.is_read = false
    unreadCount.value += 1
  }
}

async function markAllRead() {
  if (!unreadCount.value) return

  markingAll.value = true

  try {
    await axios.post('/notifications/mark-all-read')
    notifications.value = notifications.value.map((n) => ({ ...n, is_read: true }))
    unreadCount.value = 0
  } catch (error) {
    console.error('Failed to mark all as read', error)
  } finally {
    markingAll.value = false
  }
}

async function deleteNotification(notification) {
  actioningId.value = notification.id

  try {
    await axios.delete(`/notifications/${notification.id}`)
    notifications.value = notifications.value.filter((n) => n.id !== notification.id)
  } catch (error) {
    console.error('Failed to delete notification', error)
  } finally {
    actioningId.value = null
  }
}

function formatTimestamp(value) {
  if (!value) return ''

  const date = new Date(value)
  const diffMs = Date.now() - date.getTime()
  const diffMinutes = Math.round(diffMs / 60000)

  if (diffMinutes < 1) return t('Just now')
  if (diffMinutes < 60) return `${diffMinutes}m ago`

  const diffHours = Math.round(diffMinutes / 60)
  if (diffHours < 24) return `${diffHours}h ago`

  return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

const heroDescription = computed(() => {
  if (!unreadCount.value) {
    return t('You are all caught up.')
  }

  return t(
    unreadCount.value === 1
      ? 'You have :count unread notification.'
      : 'You have :count unread notifications.',
    { count: unreadCount.value },
  )
})

const breadcrumbItems = computed(() => [
  { label: t('Dashboard'), href: '/dashboard' },
  { label: t('Notifications'), current: true },
])
</script>

<template>
  <Head :title="t('Notifications')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <div class="flex flex-wrap items-start justify-between gap-4">
        <PageHero
          :eyebrow="t('Activity')"
          :title="t('Notifications')"
          :description="heroDescription"
        />
        <button
          type="button"
          class="flex shrink-0 items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
          :disabled="!unreadCount || markingAll"
          @click="markAllRead"
        >
          <Check class="h-3.5 w-3.5" />
          {{ t('Mark all as read') }}
        </button>
      </div>

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex gap-6 border-b border-slate-200 dark:border-gray-800">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="border-b-2 px-1 pb-3 text-sm font-semibold transition"
            :class="activeTab === tab.key
              ? 'border-blue-900 text-blue-900 dark:border-blue-500 dark:text-blue-400'
              : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </div>

        <div v-if="filteredNotifications.length === 0" class="py-8 text-center text-sm text-slate-500 dark:text-gray-400">
          {{ t('No notifications found.') }}
        </div>

        <div v-else class="divide-y divide-slate-100 dark:divide-gray-800">
          <article
            v-for="n in filteredNotifications"
            :key="n.id"
            class="flex flex-wrap items-start gap-4 py-4 transition sm:flex-nowrap"
            :class="n.is_read ? 'cursor-default' : 'cursor-pointer hover:bg-slate-50 dark:hover:bg-gray-800/40'"
            @click="markRead(n)"
          >
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="n.type === 'instructor_approval'
                ? 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400'
                : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'"
            >
              <Mail v-if="n.type === 'instructor_approval'" class="h-5 w-5" />
              <Bell v-else class="h-5 w-5" />
            </span>

            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <p class="font-semibold text-slate-800 dark:text-gray-100">{{ n.title }}</p>
                <span v-if="!n.is_read" class="h-2 w-2 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" />
              </div>
              <p class="mt-1 flex flex-wrap items-center text-sm text-slate-600 dark:text-gray-300">
                <span>{{ splitMessage(n.message).text }}</span>
                <span
                  v-if="splitMessage(n.message).code"
                  class="ml-1 inline-flex items-center gap-1 rounded-md bg-slate-100 py-0.5 pr-1 pl-1.5 dark:bg-gray-800"
                >
                  <span class="font-mono text-sm font-semibold tracking-wider text-slate-900 dark:text-gray-50">{{ splitMessage(n.message).code }}</span>
                  <button
                    type="button"
                    class="rounded p-0.5 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-50"
                    :title="copiedId === n.id ? t('Copied!') : t('Copy code')"
                    @click.stop="copyCode(splitMessage(n.message).code, n.id)"
                  >
                    <Check v-if="copiedId === n.id" class="h-3 w-3 text-green-600 dark:text-green-400" />
                    <Copy v-else class="h-3 w-3" />
                  </button>
                </span>
              </p>
              <p class="mt-1.5 text-xs text-slate-400 dark:text-gray-500">{{ formatTimestamp(n.created_at) }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
              <template v-if="n.approval_status === 'pending'">
                <button
                  type="button"
                  class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700 disabled:opacity-50"
                  :disabled="actioningId === n.id"
                  @click.stop="actOnNotification(n, 'reject')"
                >
                  {{ $t('Reject') }}
                </button>
              </template>
              <span
                v-else-if="n.approval_status === 'approved'"
                class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-400"
              >
                {{ $t('Approved') }}
              </span>
              <span
                v-else-if="n.approval_status === 'rejected'"
                class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-400"
              >
                {{ $t('Rejected') }}
              </span>

              <button
                type="button"
                class="rounded-lg border border-slate-200 p-1.5 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:border-gray-700 dark:text-gray-500 dark:hover:border-red-500/30 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                :disabled="actioningId === n.id"
                :title="t('Delete notification')"
                @click.stop="deleteNotification(n)"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
          </article>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
