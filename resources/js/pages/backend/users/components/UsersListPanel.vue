<script setup>
import { Search } from '@lucide/vue'
import { formatRole, roleBadgeClass } from '../../../../lib/roleBadge'

defineProps({
  totalUsers: { type: Number, required: true },
  filteredUserSummaries: { type: Array, required: true },
})

const userSearch = defineModel('userSearch', { type: String, required: true })
const selectedUserId = defineModel('selectedUserId', { type: String, required: true })
</script>

<template>
  <aside class="self-start rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between px-2 pb-3">
      <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Users</h2>
      <span class="text-sm font-semibold text-slate-400 dark:text-gray-500">{{ totalUsers }}</span>
    </div>

    <div class="px-2 pb-3">
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
        <input v-model="userSearch" type="text" placeholder="Search..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
      </div>
    </div>

    <div class="max-h-[640px] space-y-2 overflow-y-auto pr-1">
      <p v-if="filteredUserSummaries.length === 0" class="px-2 py-6 text-center text-sm text-slate-500 dark:text-gray-400">No users found.</p>
      <button v-for="user in filteredUserSummaries" :key="user.id" type="button" class="flex w-full items-start justify-between gap-3 rounded-xl px-4 py-3 text-left transition" :class="user.selected ? 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800'" @click="selectedUserId = String(user.id)">
        <span class="min-w-0">
          <span class="block truncate text-sm font-bold">{{ user.name }}</span>
          <span class="mt-1 block truncate text-xs text-slate-500 dark:text-gray-400">{{ user.email }}</span>
          <span class="mt-2 flex flex-wrap gap-1">
            <span v-for="role in user.roles" :key="role" :class="['inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold', roleBadgeClass(role)]">{{ formatRole(role) }}</span>
          </span>
        </span>
        <span class="shrink-0 rounded-full bg-white px-2 py-1 text-xs font-bold text-blue-700 dark:bg-gray-800 dark:text-blue-400">{{ user.total_permissions_count }}</span>
      </button>
    </div>
  </aside>
</template>
