<script setup>
import { formatRole, roleBadgeClass } from '../../../../lib/roleBadge'

defineProps({
  roleSummaries: { type: Array, required: true },
  deleteDisabledReason: { type: Function, required: true },
  openCreateModal: { type: Function, required: true },
  deleteRole: { type: Function, required: true },
})

const selectedRoleId = defineModel('selectedRoleId', { type: String, required: true })
</script>

<template>
  <aside class="self-start rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between pb-3">
      <h2 class="text-base font-bold text-slate-900 dark:text-gray-100">Roles</h2>
      <button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300" @click="openCreateModal">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M12 5v14M5 12h14" />
        </svg>
        Create Role
      </button>
    </div>

    <div class="space-y-2">
      <div v-for="role in roleSummaries" :key="role.id" class="group flex w-full items-center gap-2 rounded-xl px-4 py-3 transition" :class="role.selected ? 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-gray-800'">
        <button type="button" class="flex flex-1 items-center justify-between text-left" @click="selectedRoleId = String(role.id)">
          <span>
            <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-semibold', roleBadgeClass(role.name)]">{{ formatRole(role.name) }}</span>
            <span class="mt-2 block text-xs text-slate-500 dark:text-gray-400">{{ role.users_count }} users</span>
          </span>
          <span class="text-sm font-semibold text-slate-400 dark:text-gray-500">{{ role.permissions.length }}</span>
        </button>
        <!-- Delete is greyed out (not hidden) so the tooltip can explain why, via deleteDisabledReason -->
        <button type="button" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition" :class="role.deletable ? 'text-slate-400 hover:bg-red-50 hover:text-red-600 dark:text-gray-500 dark:hover:bg-red-500/10 dark:hover:text-red-400' : 'cursor-not-allowed text-slate-200 dark:text-gray-700'" :disabled="!role.deletable" :title="deleteDisabledReason(role)" :aria-label="`Delete ${formatRole(role.name)} role`" @click="deleteRole(role)">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 6h18" />
            <path d="M8 6V4h8v2" />
            <path d="M19 6l-1 14H6L5 6" />
            <path d="M10 11v5" />
            <path d="M14 11v5" />
          </svg>
        </button>
      </div>
    </div>
  </aside>
</template>
