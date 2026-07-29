<script setup>
import { formatRole } from '../../../../lib/roleBadge'

defineProps({
  selectedRole: { type: Object, default: null },
  assignUsersForm: { type: Object, required: true },
  filteredUsers: { type: Array, required: true },
  selectedUserCount: { type: Number, required: true },
  isUserSelected: { type: Function, required: true },
  toggleUser: { type: Function, required: true },
  saveAssignedUsers: { type: Function, required: true },
})

const userSearch = defineModel('userSearch', { type: String, required: true })
</script>

<template>
  <form class="self-start rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900" @submit.prevent="saveAssignedUsers">
    <div class="border-b border-slate-200 p-5 dark:border-gray-800">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">{{ $t('Assign Users to Role') }}</h2>
          <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">{{ selectedRole ? formatRole(selectedRole.name) : $t('Select a role') }}</p>
        </div>
        <button type="submit" :disabled="!selectedRole || assignUsersForm.processing" class="shrink-0 rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70">{{ assignUsersForm.processing ? $t('Assigning...') : $t('Assign User') }}</button>
      </div>

      <label class="mt-4 block">
        <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">{{ $t('Search') }}</span>
        <input v-model="userSearch" type="text" :placeholder="$t('Search users...')" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
      </label>
    </div>

    <p v-if="assignUsersForm.errors.users" class="mx-5 mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">{{ assignUsersForm.errors.users }}</p>

    <div class="max-h-[560px] divide-y divide-slate-100 overflow-y-auto dark:divide-gray-800">
      <label v-for="user in filteredUsers" :key="user.id" class="flex cursor-pointer items-center gap-3 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-gray-800/60">
        <!-- :checked/@change instead of v-model: selection lives in assignUsersForm.users, not a per-item boolean -->
        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-700 accent-blue-700 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:accent-blue-500 dark:focus:ring-blue-500/20" :checked="isUserSelected(user.id)" @change="toggleUser(user.id)">

        <span class="min-w-0 flex-1">
          <span class="block truncate text-sm font-bold text-slate-900 dark:text-gray-100">{{ user.name }}</span>
          <span class="block truncate text-xs text-slate-500 dark:text-gray-400">{{ user.email }}</span>
        </span>

        <div class="shrink-0 text-right">
          <span class="block text-xs font-semibold text-blue-700 dark:text-blue-400">{{ user.roles?.[0] ? formatRole(user.roles[0]) : $t('No Role') }}</span>
          <span class="block text-[11px] text-slate-400 dark:text-gray-500">{{ $t(':count permissions', { count: user.total_permissions_count }) }}</span>
        </div>
      </label>

      <div v-if="filteredUsers.length === 0" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-gray-400">{{ $t('No users found.') }}</div>
    </div>

    <div class="border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/40">
      <span class="text-sm text-slate-500 dark:text-gray-400">{{ $t('Selected :count users', { count: selectedUserCount }) }}</span>
    </div>
  </form>
</template>
