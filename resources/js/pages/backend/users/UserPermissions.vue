<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import UserDirectPermissionsMatrixPanel from './components/UserDirectPermissionsMatrixPanel.vue'
import UsersListPanel from './components/UsersListPanel.vue'
import { useUserPermissions } from './composables/useUserPermissions'

// Debounces a fast-changing ref (raw keystrokes) into a settled value that
// computed filters can key off, so large lists don't re-filter on every key.
function useDebouncedValue(sourceRef, delay = 250) {
  const debounced = ref(sourceRef.value)
  let timer = null

  watch(sourceRef, (value) => {
    if (timer) {
      clearTimeout(timer)
    }

    timer = window.setTimeout(() => {
      debounced.value = value
    }, delay)
  })

  return debounced
}

// UI state only (selection, pagination, search text) - domain data and save logic live in useUserPermissions.
const selectedUserId = ref('')
const matrixPage = ref(1)
const showOnlyChecked = ref(false)

const userSearchInput = ref('')
const userSearchQuery = useDebouncedValue(userSearchInput)

const moduleSearchInput = ref('')
const moduleSearchQuery = useDebouncedValue(moduleSearchInput)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Users', href: '/dashboard/users' },
  { label: 'Permissions', current: true },
]

const {
  form,
  selectedUser,
  totalUsers,
  directPermissionUsers,
  selectedDirectCount,
  selectedRoleCount,
  selectedTotalCount,
  actions,
  filteredResources,
  checkedResourcesCount,
  matrixLastPage,
  paginatedResources,
  matrixStart,
  matrixEnd,
  filteredUserSummaries,
  permissionName,
  togglePermission,
  isDirectPermission,
  isRolePermission,
  allPermissionsSelected,
  hasUnsavedPermissionChanges,
  toggleAllPermissions,
  clearAllPermissions,
  savePermissions,
} = useUserPermissions({ selectedUserId, userSearchQuery, moduleSearchQuery, matrixPage, showOnlyChecked })

function changeMatrixPage(page) {
  matrixPage.value = page
}
</script>

<template>
  <Head title="User & Permission" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <!-- Top summary: title on the left, compact quick stats on the right. -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <PageHero eyebrow="User Management" title="User & Permission" description="Give selected users extra permissions without changing their role." />

        <!-- Summary tiles: derived from users/permissions props and the current selection, not fetched separately -->
        <div class="grid grid-cols-4 gap-3">
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Total Users</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ totalUsers }}</p>
            <p class="mt-0.5 text-[11px] text-emerald-600 dark:text-emerald-400">Available for assignment</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Direct Users</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ directPermissionUsers }}</p>
            <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-400">Have custom permissions</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Direct Selected</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ selectedDirectCount }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">User-specific access</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-gray-400">Total Selected</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ selectedTotalCount }}</p>
            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400">Role plus direct access</p>
          </div>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[320px_1fr]">
        <UsersListPanel
          v-model:user-search="userSearchInput"
          v-model:selected-user-id="selectedUserId"
          :total-users="totalUsers"
          :filtered-user-summaries="filteredUserSummaries"
        />

        <UserDirectPermissionsMatrixPanel
          v-model:module-search="moduleSearchInput"
          v-model:show-only-checked="showOnlyChecked"
          :selected-user="selectedUser"
          :form="form"
          :actions="actions"
          :paginated-resources="paginatedResources"
          :filtered-resources="filteredResources"
          :checked-resources-count="checkedResourcesCount"
          :matrix-page="matrixPage"
          :matrix-last-page="matrixLastPage"
          :matrix-start="matrixStart"
          :matrix-end="matrixEnd"
          :selected-role-count="selectedRoleCount"
          :selected-direct-count="selectedDirectCount"
          :all-permissions-selected="allPermissionsSelected"
          :has-unsaved-permission-changes="hasUnsavedPermissionChanges"
          :permission-name="permissionName"
          :is-direct-permission="isDirectPermission"
          :is-role-permission="isRolePermission"
          :toggle-permission="togglePermission"
          :toggle-all-permissions="toggleAllPermissions"
          :clear-all-permissions="clearAllPermissions"
          :save-permissions="savePermissions"
          :change-matrix-page="changeMatrixPage"
        />
      </div>
    </section>
  </DashboardLayout>
</template>
